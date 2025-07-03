<?php

/* ===== /Core/Template/CoreliaTemplate.php ===== */

namespace Corelia\Template;

/**
 * Moteur de template minimaliste façon Twig pour CoreliaPHP.
 * Gère l'héritage, les blocs, les variables, les boucles, les conditions et l'inclusion.
 */
class CoreliaTemplate
{
    
    // Chemin du fichier template principal à utiliser pour le rendu
    protected string $templatePath;

    // Tableau associatif des blocs définis dans le(s) template(s)
    // (ex : 'content' => '<h1>...</h1>')
    protected array $blocks = [];

    // Pile utilisée pour gérer l'imbrication des blocs lors du parsing (utile pour l'héritage)
    protected array $blockStack = [];

    // Chemin du template parent si le template courant utilise {% extends ... %}
    // (null si pas d'héritage)
    protected ?string $parentTemplate = null;

    /**
     * Constructeur de la classe CoreliaTemplate.
     * Initialise le moteur avec le chemin du template à utiliser.
     *
     * @param string $templatePath      Chemin du fichier template à charger
     */
    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Rend le template principal avec les variables fournies.
     * Gère l'héritage de template (extends) et la fusion des blocs.
     *
     * @param array $vars               Variables à injecter dans le template
     * @return string                   HTML généré
     */
    public function render(array $vars = []): string
    {
        $globals = [
            'now' => new \DateTime(),
            'app' => [
                'name' => 'CoreliaPHP',
            ],
        ];
        $vars = array_merge($globals, $vars);

        $this->collectBlocks($this->templatePath, $vars);

        if ($this->parentTemplate) {
            $parent = new self($this->parentTemplate);
            $parent->blocks = $this->blocks;
            return $parent->render($vars);
        } else {
            return $this->renderTemplate($this->templatePath, $vars, $this->blocks);
        }
    }

    /**
     * Recherche tous les blocs dans un template (et ses parents).
     * Gère également l'héritage avec {% extends ... %}.
     *
     * @param string $file              Chemin du template à analyser
     * @param array $vars               Variables disponibles lors de l'analyse
     */
    protected function collectBlocks(string $file, array $vars)
    {
        if (!file_exists($file)) {
            error_log("DEBUG: Fichier template introuvable: $file");
            return;
        }
        $tpl = file_get_contents($file);

        if (preg_match('/\{% extends [\'"]([^\'"]+)[\'"] %\}/', $tpl, $m)) {
            $this->parentTemplate = $this->resolvePath($m[1], $file);
            $tpl = str_replace($m[0], '', $tpl);
        }

        preg_replace_callback('/\{% block (\w+) %\}(.*?)\{% endblock %\}/s', function ($m) {
            $blockName = $m[1];
            $blockContent = $m[2];
            if (!isset($this->blocks[$blockName])) {
                $this->blocks[$blockName] = $blockContent;
            }
            return '';
        }, $tpl);

        if ($this->parentTemplate) {
            $parent = new self($this->parentTemplate);
            $parent->blocks = $this->blocks;
            $parent->collectBlocks($this->parentTemplate, $vars);
            $this->blocks = array_merge($parent->blocks, $this->blocks);
        }
    }

    /**
     * Rend un fichier template avec les variables et blocs fournis.
     * Remplace les blocs par leur contenu hérité et parse toutes les instructions.
     *
     * @param string $file              Chemin du template à rendre
     * @param array $vars               Variables à injecter
     * @param array $blocks             Blocs hérités à utiliser
     * @return string                   HTML généré
     */
    protected function renderTemplate(string $file, array $vars, array $blocks): string
    {
        if (!file_exists($file)) {
            error_log("DEBUG: Fichier template introuvable: $file");
            return "<!-- Template non trouvé pour : $file -->";
        }
        $tpl = file_get_contents($file);

        $tpl = preg_replace('/\{% extends [\'"][^\'"]+[\'"] %\}/', '', $tpl);

        $tpl = preg_replace_callback('/\{% block (\w+) %\}(.*?)\{% endblock %\}/s', function ($m) use ($blocks, $vars, $file) {
            $blockName = $m[1];
            $blockContent = $blocks[$blockName] ?? $m[2];
            return $this->parseString($this->parseAll($blockContent, $vars, $file), $vars);
        }, $tpl);

        return $this->parseAll($tpl, $vars, $file);
    }

    /**
     * Parse toutes les instructions du template (set, include, for, if...).
     * C'est la fonction centrale de transformation du template en HTML final.
     *
     * @param string $tpl               Le contenu du template à parser
     * @param array  $vars              Les variables du contexte (passées par référence)
     * @param string $file              Le chemin du template courant (pour les includes)
     * @return string                   Le template transformé
     */
    protected function parseAll(string $tpl, array &$vars, string $file): string
    {
        // Supprime les commentaires Twig {# ... #}
        $tpl = preg_replace('/\{#.*?#\}/s', '', $tpl);

        // {% set var = value %}
        $tpl = preg_replace_callback('/\{% set (\w+) = (.+?) %\}/s', function ($m) use (&$vars) {
            $name = $m[1];
            $value = trim($m[2]);
            if ((substr($value, 0, 1) === '[' && substr($value, -1) === ']') ||
                (substr($value, 0, 1) === '{' && substr($value, -1) === '}')) {
                $jsonVal = preg_replace_callback('/\'([^\']*)\'/', fn($matches) => '"' . addcslashes($matches[1], '"') . '"', $value);
                $json = @json_decode($jsonVal, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $vars[$name] = $json;
                    return '';
                }
            }
            if (preg_match('/^["\'](.*)["\']$/', $value, $str)) {
                $vars[$name] = $str[1];
            } elseif (preg_match('/^\[(.*?)\]$/', $value, $arr)) {
                $arrValues = array_map('trim', explode(',', $arr[1]));
                $vars[$name] = array_map(function($v) {
                    $v = trim($v);
                    if (preg_match('/^["\'](.*)["\']$/', $v, $m)) return $m[1];
                    return is_numeric($v) ? 0+$v : $v;
                }, $arrValues);
            } elseif (is_numeric($value)) {
                $vars[$name] = 0 + $value;
            } elseif ($value === 'true') {
                $vars[$name] = true;
            } elseif ($value === 'false') {
                $vars[$name] = false;
            } else {
                // Correction : toujours resolveTwigVar pour les expressions
                $resolved = $this->resolveTwigVar($value, $vars);
                $vars[$name] = $resolved !== null ? $resolved : $value;
            }
            return '';
        }, $tpl);

        // {% include 'file' %}
        $tpl = preg_replace_callback('/\{% include [\'"]([^\'"]+)[\'"] %\}/', function ($m) use ($vars, $file) {
            $incPath = $this->resolvePath($m[1], $file);
            return $this->renderTemplate($incPath, $vars, $this->blocks);
        }, $tpl);

        // Boucles for imbriquées (parseur récursif)
        $tpl = $this->parseForBlocks($tpl, $vars, $file);

        // Gestion récursive des blocs if/elseif/else/endif imbriqués
        $tpl = $this->parseIfBlocks($tpl, $vars, $file);

        return $tpl;
    }

    /**
     * Parse récursivement les blocs for imbriqués.
     * Gère les instructions {% for ... in ... %} ... {% endfor %}
     * Supporte aussi {% for key, value in array %} (clé/valeur).
     *
     * @param string $tpl               Le contenu du template à parser
     * @param array  $vars              Les variables du contexte (passées par référence)
     * @param string $file              Le chemin du template courant
     * @return string                   Le template transformé avec les boucles déroulées
     */
    protected function parseForBlocks(string $tpl, array &$vars, string $file): string
    {
        $pattern = '/\{% for (\w+)(?:,\s*(\w+))? in ([\w\.]+) %\}/';
        while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];
            $var1 = $m[1][0];
            $var2 = isset($m[2][0]) ? $m[2][0] : null;
            $arrExpr = $m[3][0];
            $rest = substr($tpl, $start + strlen($m[0][0]));
            $depth = 1;
            $offset = 0;
            $len = strlen($rest);

            while ($depth > 0 && $offset < $len) {
                if (preg_match('/\{% (for|endfor) [^%]*%\}/', $rest, $tag, PREG_OFFSET_CAPTURE, $offset)) {
                    $tagStart = $tag[0][1];
                    $tagType = $tag[1][0];
                    if ($tagType === 'for') {
                        $offset = $tagStart + strlen($tag[0][0]);
                        $depth++;
                    } elseif ($tagType === 'endfor') {
                        $depth--;
                        if ($depth === 0) {
                            $block = substr($rest, 0, $tagStart);
                            $after = substr($rest, $tagStart + strlen($tag[0][0]));
                            // Résolution de la variable de boucle
                            $arr = $this->resolveTwigVar($arrExpr, $vars);
                            $out = '';
                            if (is_array($arr)) {
                                $i = 0;
                                $lenArr = count($arr);
                                foreach ($arr as $k => $v) {
                                    $localVars = $vars;
                                    if ($var2 !== null) {
                                        $localVars[$var1] = $k;
                                        $localVars[$var2] = $v;
                                    } else {
                                        $localVars[$var1] = $v;
                                    }
                                    $localVars['loop'] = [
                                        'index' => $i + 1,
                                        'index0' => $i,
                                        'revindex' => $lenArr - $i,
                                        'revindex0' => $lenArr - $i - 1,
                                        'first' => $i === 0,
                                        'last' => $i === $lenArr - 1,
                                        'length' => $lenArr,
                                    ];
                                    $out .= $this->parseString($this->parseAll($block, $localVars, $file), $localVars);
                                    $i++;
                                }
                            }
                            $tpl = substr($tpl, 0, $start) . $out . $after;
                            // Redémarre le parsing car tout a bougé
                            return $this->parseForBlocks($tpl, $vars, $file);
                        }
                        $offset = $tagStart + strlen($tag[0][0]);
                    }
                } else {
                    break;
                }
            }
            break;
        }
        return $tpl;
    }

    /**
     * Parse récursivement les blocs if/elseif/else/endif imbriqués.
     * Gère les instructions {% if ... %} ... {% elseif ... %} ... {% else %} ... {% endif %}
     * Supporte les opérateurs ==, !=, in, not in, et les expressions simples.
     *
     * @param string $tpl               Le contenu du template à parser
     * @param array  $vars              Les variables du contexte (passées par référence)
     * @param string $file              Le chemin du template courant
     * @return string                   Le template transformé avec les conditions évaluées
     */
    protected function parseIfBlocks(string $tpl, array &$vars, string $file): string
    {
        $pattern = '/\{% if ([^%]+) %\}/';
        while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];
            $cond = trim($m[1][0]);
            $rest = substr($tpl, $start + strlen($m[0][0]));
            $depth = 1;
            $offset = 0;
            $len = strlen($rest);
            $parts = [];
            $current = '';
            $currentCond = $cond;
            $mode = 'if';

            while ($depth > 0 && $offset < $len) {
                if (preg_match('/\{% (if|elseif|else|endif)([^%]*) %\}/', $rest, $tag, PREG_OFFSET_CAPTURE, $offset)) {
                    $tagStart = $tag[0][1];
                    $tagType = $tag[1][0];
                    $tagCond = isset($tag[2][0]) ? trim($tag[2][0]) : '';
                    $before = substr($rest, $offset, $tagStart - $offset);

                    if ($tagType === 'if') {
                        $current .= $before . $tag[0][0];
                        $offset = $tagStart + strlen($tag[0][0]);
                        $depth++;
                    } elseif ($tagType === 'elseif' || $tagType === 'else' || $tagType === 'endif') {
                        if ($depth === 1) {
                            $current .= $before;
                            if ($mode === 'if' || $mode === 'elseif') {
                                $parts[] = ['cond' => $currentCond, 'block' => $current];
                            } elseif ($mode === 'else') {
                                $parts[] = ['cond' => null, 'block' => $current];
                            }
                            $current = '';
                            if ($tagType === 'elseif') {
                                $currentCond = $tagCond;
                                $mode = 'elseif';
                            } elseif ($tagType === 'else') {
                                $mode = 'else';
                            } elseif ($tagType === 'endif') {
                                $depth--;
                            }
                            $offset = $tagStart + strlen($tag[0][0]);
                            if ($depth === 0) break;
                        } else {
                            $current .= $before . $tag[0][0];
                            $offset = $tagStart + strlen($tag[0][0]);
                            if ($tagType === 'endif') $depth--;
                        }
                    }
                } else {
                    $current .= substr($rest, $offset);
                    break;
                }
            }

            if ($mode === 'else' && $current !== '') {
                $parts[] = ['cond' => null, 'block' => $current];
            }

            // Évalue les conditions
            $result = '';
            foreach ($parts as $part) {
                if ($part['cond'] === null) {
                    $result = $this->parseAll($part['block'], $vars, $file);
                    break;
                }
                $expr = $part['cond'];
                // == et !=
                if (preg_match('/^((?:[\w\.]+)|(?:-?\d+)|(?:["\'][^"\']+["\']))\s*(==|!=)\s*((?:[\w\.]+)|(?:-?\d+)|(?:["\'][^"\']+["\']))\s*$/', $expr, $cmp)) {
                    $leftRaw = trim($cmp[1]);
                    $op = trim($cmp[2]);
                    $rightRaw = trim($cmp[3]);
                    if (preg_match('/^["\'](.*)["\']$/', $leftRaw, $str)) {
                        $left = $str[1];
                    } elseif (is_numeric($leftRaw)) {
                        $left = 0 + $leftRaw;
                    } elseif ($leftRaw === 'true') {
                        $left = true;
                    } elseif ($leftRaw === 'false') {
                        $left = false;
                    } else {
                        $left = $this->resolveTwigVar($leftRaw, $vars);
                    }
                    if (preg_match('/^["\'](.*)["\']$/', $rightRaw, $str)) {
                        $right = $str[1];
                    } elseif (is_numeric($rightRaw)) {
                        $right = 0 + $rightRaw;
                    } elseif ($rightRaw === 'true') {
                        $right = true;
                    } elseif ($rightRaw === 'false') {
                        $right = false;
                    } else {
                        $right = $this->resolveTwigVar($rightRaw, $vars);
                    }
                    $ok = ($op === '==') ? ($left == $right) : ($left != $right);
                    if ($ok) {
                        $result = $this->parseAll($part['block'], $vars, $file);
                        break;
                    }
                }
                // in / not in (clé de tableau associatif)
                elseif (preg_match('/^([\w\.]+)\s+(not\s+in|in)\s+([\w\.]+)$/', $expr, $cmp)) {
                    $left = $this->resolveTwigVar(trim($cmp[1]), $vars);
                    $op = trim($cmp[2]);
                    $rightArr = $this->resolveTwigVar(trim($cmp[3]), $vars);
                    $in = is_array($rightArr) && array_key_exists($left, $rightArr);
                    $ok = ($op === 'in') ? $in : !$in;
                    if ($ok) {
                        $result = $this->parseAll($part['block'], $vars, $file);
                        break;
                    }
                }
                // in / not in (tableau littéral)
                elseif (preg_match('/^([\w\.]+)\s+(not\s+in|in)\s+(\[.+\])$/', $expr, $cmp)) {
                    $left = $this->resolveTwigVar(trim($cmp[1]), $vars);
                    $op = trim($cmp[2]);
                    $rightRaw = trim($cmp[3]);
                    $rightRaw = trim($rightRaw);
                    if (substr($rightRaw, 0, 1) === '[' && substr($rightRaw, -1) === ']') {
                        $rightRaw = substr($rightRaw, 1, -1);
                        $arr = [];
                        foreach (explode(',', $rightRaw) as $v) {
                            $v = trim($v);
                            if (preg_match('/^["\'](.*)["\']$/', $v, $m)) {
                                $arr[] = $m[1];
                            } elseif (is_numeric($v)) {
                                $arr[] = 0 + $v;
                            } elseif ($v === 'true') {
                                $arr[] = true;
                            } elseif ($v === 'false') {
                                $arr[] = false;
                            } else {
                                $arr[] = $v;
                            }
                        }
                    } else {
                        $arr = [];
                    }
                    $in = in_array($left, $arr, true);
                    $ok = ($op === 'in') ? $in : !$in;
                    if ($ok) {
                        $result = $this->parseAll($part['block'], $vars, $file);
                        break;
                    }
                }
                // Expression simple
                else {
                    $value = $this->resolveTwigVar($expr, $vars);
                    if ($value) {
                        $result = $this->parseAll($part['block'], $vars, $file);
                        break;
                    }
                }
            }

            // Remplace le bloc complet
            $tpl = substr($tpl, 0, $start) . $result . substr($rest, $offset);
        }
        return $tpl;
    }

    /**
     * Remplace les expressions {{ ... }} dans le template par leur valeur.
     * Gère les filtres de base (upper, lower, date, raw), l'accès aux tableaux, et le debug.
     *
     * @param string $tpl               Le contenu du template à parser
     * @param array  $vars              Les variables du contexte
     * @return string                   Le template transformé avec les variables remplacées
     */
    protected function parseString(string $tpl, array $vars): string
    {
        // {{ dump(variable) }}
        $tpl = preg_replace_callback('/\{\{\s*dump\((.*?)\)\s*\}\}/', function($m) use ($vars) {
            $expr = trim($m[1]);
            $val = null;
            if (preg_match('/^["\'](.+)["\']$/', $expr, $mm)) {
                $val = $mm[1];
            } else {
                $val = $this->resolveTwigVar($expr, $vars);
            }
            ob_start();
            echo '<pre style="background:#222;color:#eee;padding:10px;border-radius:6px;font-size:13px">';
            var_dump($val);
            echo '</pre>';
            return ob_get_clean();
        }, $tpl);

        // {{ tableau[clé] }}
        $tpl = preg_replace_callback('/\{\{\s*([\w\.]+)\[([^\]\}]+)\]\s*\}\}/', function($m) use ($vars) {
            $array = $this->resolveTwigVar($m[1], $vars);
            $keyExpr = trim($m[2]);
            if (preg_match('/^["\'](.+)["\']$/', $keyExpr, $mm)) {
                $key = $mm[1];
            } else {
                $key = $this->resolveTwigVar($keyExpr, $vars);
            }
            return isset($array[$key]) ? htmlspecialchars((string)$array[$key], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';
        }, $tpl);

        // {{ var }}, {{ var.prop }}, filtres de base
        return preg_replace_callback('/\{\{\s*(?:(["\'])(.*?)\1|([\w\.]+))((?:\|[\w]+(?:\([^\)]*\))?)*)\s*\}\}/', function ($m) use ($vars) {
            $val = '';
            if (isset($m[2]) && $m[2] !== '') {
                $val = $m[2];
            } elseif (isset($m[3]) && $m[3] !== '') {
                $val = $this->resolveTwigVar($m[3], $vars);
            }

            if (!empty($m[4])) {
                $filters = explode('|', trim($m[4], '|'));
                foreach ($filters as $filter) {
                    if ($filter === 'raw') continue;
                    if ($filter === 'upper') $val = mb_strtoupper($val);
                    elseif ($filter === 'lower') $val = mb_strtolower($val);
                    elseif (preg_match('/^date\([\'"]([^\'"]+)[\'"]\)$/', $filter, $dm)) {
                        if ($val instanceof \DateTime) $val = $val->format($dm[1]);
                        elseif (is_numeric($val)) $val = date($dm[1], $val);
                        else {
                            $dt = @strtotime($val);
                            $val = $dt ? date($dm[1], $dt) : $val;
                        }
                    }
                }
                if (in_array('raw', $filters)) {
                    if ($val instanceof \DateTime) return $val->format('Y-m-d H:i:s');
                    if (is_object($val)) return method_exists($val, '__toString') ? (string)$val : '[object]';
                    return $val;
                }
            }
            if ($val instanceof \DateTime) return htmlspecialchars($val->format('Y-m-d H:i:s'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if (is_object($val)) return htmlspecialchars(method_exists($val, '__toString') ? (string)$val : '[object]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $tpl);
    }

    /**
     * Résout une expression Twig (notation pointée ou tableau) dans le contexte des variables.
     * Exemples : foo.bar, foo[bar.key], etc.
     *
     * @param string $expr              Expression à résoudre
     * @param array $vars               Variables du contexte
     * @return mixed|null               Valeur trouvée ou null si non trouvée
     */
    protected function resolveTwigVar(string $expr, array $vars)
    {
        // Accès dynamique : foo[bar.key].baz
        if (preg_match_all('/([\w]+)(\[[^\]]+\])*/', $expr, $matches)) {
            $parts = [];
            foreach ($matches[1] as $i => $base) {
                $parts[] = $base;
                if (!empty($matches[2][$i])) {
                    preg_match_all('/\[([^\]]+)\]/', $matches[2][$i], $arrMatches);
                    foreach ($arrMatches[1] as $arrKeyExpr) {
                        $parts[] = $this->resolveTwigVar($arrKeyExpr, $vars);
                    }
                }
            }
            $val = $vars;
            foreach ($parts as $part) {
                if (is_array($val) && array_key_exists($part, $val)) $val = $val[$part];
                elseif (is_object($val) && isset($val->$part)) $val = $val->$part;
                else return null;
            }
            return $val;
        }
        // Fallback simple
        $parts = explode('.', $expr);
        $val = $vars;
        foreach ($parts as $part) {
            if (is_array($val) && array_key_exists($part, $val)) $val = $val[$part];
            elseif (is_object($val) && isset($val->$part)) $val = $val->$part;
            else return null;
        }
        return $val;
    }

    /**
     * Résout le chemin absolu d'un template à inclure ou à hériter.
     * Gère les chemins relatifs, absolus, notation module::template, etc.
     *
     * @param string $relative          Chemin relatif ou spécial du template
     * @param string $currentFile       Chemin du template courant (pour le relatif)
     * @return string                   Chemin absolu du template résolu
     */
    protected function resolvePath(string $relative, string $currentFile): string
    {
        if (strpos($relative, '::') !== false) {
            [$module, $tpl] = explode('::', $relative, 2);
            $path = __DIR__ . "/../../modules/{$module}/Views/{$tpl}";
            $real = realpath($path);
            return $real !== false ? $real : $path;
        }
        if ($relative[0] === '/' || preg_match('/^[A-Za-z]:\\\\/', $relative)) {
            $real = realpath($relative);
            return $real !== false ? $real : $relative;
        }
        if (substr($relative, -5) === '.ctpl') {
            $appView = __DIR__ . "/../../src/Views/{$relative}";
            $real = realpath($appView);
            if ($real !== false) return $real;
        }
        $rel = dirname($currentFile) . '/' . $relative;
        $real = realpath($rel);
        return $real !== false ? $real : $rel;
    }
}
