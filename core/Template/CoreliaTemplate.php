<?php

namespace Corelia\Template;

/**
 * Moteur de template maison inspiré de Twig (80% des usages courants).
 * Gère blocs, héritage, boucles, conditions, includes, appels de méthodes/propriétés, filtres de base.
 */
class CoreliaTemplate
{
    /** @var string Chemin du template principal à utiliser */
    protected string $templatePath;

    /** @var array Blocs définis dans les templates (pour l’héritage) */
    protected array $blocks = [];

    /** @var string|null Chemin du template parent si extends */
    protected ?string $parentTemplate = null;

    /**
     * Constructeur : initialise le moteur avec le chemin du template principal.
     * @param string $templatePath Chemin du template principal à utiliser
     */
    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Rend le template avec les variables fournies.
     * Gère l’héritage et la fusion des blocs.
     * @param array $vars Variables à injecter dans le template
     * @return string HTML généré
     */
    public function render(array $vars = []): string
    {
        // Variables globales disponibles partout
        $globals = [
            'now' => new \DateTime(),
            'app' => ['name' => 'CoreliaPHP'],
        ];
        $vars = array_merge($globals, $vars);
        // Collecte les blocs du template courant et de ses parents
        $this->collectBlocks($this->templatePath, $vars);

        // Si héritage, rend le parent avec les blocs fusionnés
        if ($this->parentTemplate) {
            $parent = new self($this->parentTemplate);
            $parent->blocks = $this->blocks;
            return $parent->render($vars);
        }
        // Sinon, rend le template courant
        return $this->renderTemplate($this->templatePath, $vars, $this->blocks);
    }

    /**
     * Recherche tous les blocs dans un template (et ses parents).
     * Gère également l’héritage avec {% extends ... %}.
     * @param string $file Chemin du template à analyser
     * @param array $vars Variables disponibles lors de l’analyse
     * @return void
     */
    protected function collectBlocks(string $file, array $vars): void
    {
        if (!file_exists($file)) return;
        $tpl = file_get_contents($file);

        // Détection de l’héritage
        if (preg_match('/\{% extends [\'"]([^\'"]+)[\'"] %\}/', $tpl, $m)) {
            $this->parentTemplate = $this->resolvePath($m[1], $file);
            $tpl = str_replace($m[0], '', $tpl);
        }

        // Extraction des blocs
        preg_replace_callback('/\{% block (\w+) %\}(.*?)\{% endblock %\}/s', function ($m) {
            $blockName = $m[1];
            $blockContent = $m[2];
            if (!isset($this->blocks[$blockName])) {
                $this->blocks[$blockName] = $blockContent;
            }
            return '';
        }, $tpl);

        // Récupération récursive des blocs du parent si héritage
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
     * @param string $file Chemin du template à rendre
     * @param array $vars Variables à injecter
     * @param array $blocks Blocs hérités à utiliser
     * @return string HTML généré
     */
    protected function renderTemplate(string $file, array $vars, array $blocks): string
    {
        if (!file_exists($file)) return "<!-- Template non trouvé pour : $file -->";
        $tpl = file_get_contents($file);

        // Suppression de la déclaration d’héritage
        $tpl = preg_replace('/\{% extends [\'"][^\'"]+[\'"] %\}/', '', $tpl);

        // Remplacement des blocs par leur contenu
        $tpl = preg_replace_callback('/\{% block (\w+) %\}(.*?)\{% endblock %\}/s', function ($m) use ($blocks, $vars, $file) {
            $blockName = $m[1];
            $blockContent = $blocks[$blockName] ?? $m[2];
            // Parse récursivement le contenu du bloc
            return $this->parseString($this->parseAll($blockContent, $vars, $file), $vars);
        }, $tpl);

        // Parse toutes les autres instructions du template
        return $this->parseAll($tpl, $vars, $file);
    }

    /**
     * Parse toutes les instructions du template (set, include, for, if...).
     * @param string $tpl Contenu du template à parser
     * @param array &$vars Variables du contexte (passées par référence)
     * @param string $file Chemin du template courant (pour les includes)
     * @return string Template transformé
     */
    protected function parseAll(string $tpl, array &$vars, string $file): string
    {
        
        // Supprime les commentaires Twig {# ... #}
        $tpl = preg_replace('/\{#.*?#\}/s', '', $tpl);

        // Gestion des variables {% set var = value %}
        $tpl = preg_replace_callback('/\{% set (\w+) = (.+?) %\}/s', function ($m) use (&$vars) {
            $name = $m[1];
            $value = trim($m[2]);
            // Gestion des tableaux et objets JSON
            if ((substr($value, 0, 1) === '[' && substr($value, -1) === ']') ||
                (substr($value, 0, 1) === '{' && substr($value, -1) === '}')) {
                $jsonVal = preg_replace_callback('/\'([^\']*)\'/', fn($matches) => '"' . addcslashes($matches[1], '"') . '"', $value);
                $json = @json_decode($jsonVal, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $vars[$name] = $json;
                    return '';
                }
            }
            // Gestion des chaînes, nombres, booléens
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
                // Résolution d'expressions complexes
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

        // Boucles for imbriquées
        $tpl = $this->parseForBlocks($tpl, $vars, $file);

        // Conditions imbriquées
        $tpl = $this->parseIfBlocks($tpl, $vars, $file);

        $tpl = $this->parseString($tpl, $vars);
        
        return $tpl;
    }

    /**
     * Parse récursivement les blocs for imbriqués.
     * Gère {% for ... in ... %} ... {% endfor %} et {% for key, value in array %}
     * @param string $tpl Contenu du template à parser
     * @param array &$vars Variables du contexte (passées par référence)
     * @param string $file Chemin du template courant
     * @return string Template transformé avec les boucles déroulées
     */
    protected function parseForBlocks(string $tpl, array &$vars, string $file): string
    {
        $pattern = '/\{% for (\w+)(?:,\s*(\w+))? in ([^\s%]+) %\}/';
        while (preg_match($pattern, $tpl, $m, PREG_OFFSET_CAPTURE)) {
            $start = $m[0][1];
            $var1 = $m[1][0];
            $var2 = isset($m[2][0]) && $m[2][0] !== '' ? $m[2][0] : null;
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
     * Parse récursivement les blocs conditionnels imbriqués.
     * Gère {% if ... %}, {% elseif ... %}, {% else %}, {% endif %}
     * @param string $tpl Contenu du template à parser
     * @param array &$vars Variables du contexte (passées par référence)
     * @param string $file Chemin du template courant
     * @return string Template transformé avec conditions évaluées
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

            $result = '';
            foreach ($parts as $part) {
                if ($part['cond'] === null) {
                    $result = $this->parseAll($part['block'], $vars, $file);
                    break;
                }
                $expr = $part['cond'];
                if (preg_match('/^((?:[\w\.]+)|(?:-?\d+)|(?:["\'][^"\']+["\']))\s*(==|!=)\s*((?:[\w\.]+)|(?:-?\d+)|(?:["\'][^"\']+["\']))\s*$/', $expr, $cmp)) {
                    $leftRaw = trim($cmp[1]);
                    $op = trim($cmp[2]);
                    $rightRaw = trim($cmp[3]);
                    $left = $this->parseIfOperand($leftRaw, $vars);
                    $right = $this->parseIfOperand($rightRaw, $vars);
                    $ok = ($op === '==') ? ($left == $right) : ($left != $right);
                    if ($ok) {
                        $result = $this->parseAll($part['block'], $vars, $file);
                        break;
                    }
                } else {
                    $value = $this->resolveTwigVar($expr, $vars);
                    if ($value) {
                        $result = $this->parseAll($part['block'], $vars, $file);
                        break;
                    }
                }
            }
            $tpl = substr($tpl, 0, $start) . $result . substr($rest, $offset);
        }
        return $tpl;
    }

    /**
     * Analyse un opérande dans une condition if (gestion des chaînes, booléens, variables).
     * @param string $operand Expression à analyser
     * @param array $vars Variables du contexte
     * @return mixed Valeur évaluée
     */
    protected function parseIfOperand(string $operand, array $vars)
    {
        if (preg_match('/^["\'](.*)["\']$/', $operand, $str)) {
            return $str[1];
        } elseif (is_numeric($operand)) {
            return 0 + $operand;
        } elseif ($operand === 'true') {
            return true;
        } elseif ($operand === 'false') {
            return false;
        } else {
            return $this->resolveTwigVar($operand, $vars);
        }
    }

    /**
     * Remplace les expressions {{ ... }} dans le template par leur valeur.
     * Gère les filtres de base (upper, lower, date, raw), l'accès aux tableaux, et le debug.
     * @param string $tpl Contenu du template à parser
     * @param array $vars Variables du contexte
     * @return string Template transformé avec les variables remplacées
     */
    protected function parseString(string $tpl, array $vars): string
    {
        // Debug : {{ dump(variable) }}
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

        // Accès tableau : {{ tableau[clé] }}
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

        // Variables, propriétés, appels de méthodes, filtres
        return preg_replace_callback(
            '/\{\{\s*(?:(["\'])(.*?)\1|([a-zA-Z0-9_\.()]+))((?:\|[\w]+(?:\([^\)]*\))?)*)\s*\}\}/',
            function ($m) use ($vars) {
                $val = '';
                if (isset($m[2]) && $m[2] !== '') {
                    $val = $m[2];
                } elseif (isset($m[3]) && $m[3] !== '') {
                    $val = $this->resolveTwigVar($m[3], $vars);
                }
                // Filtres
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
                // Échappement HTML par défaut
                if ($val instanceof \DateTime) return htmlspecialchars($val->format('Y-m-d H:i:s'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                if (is_object($val)) return htmlspecialchars(method_exists($val, '__toString') ? (string)$val : '[object]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
            $tpl
        );
    }

    /**
     * Résout une expression Twig (notation pointée, tableau, méthode) dans le contexte des variables.
     * Exemples : foo.bar, foo[bar.key], foo.getName()
     * @param string $expr Expression à résoudre
     * @param array $vars Variables du contexte
     * @return mixed|null Valeur trouvée ou null si non trouvée
     */
    protected function resolveTwigVar(string $expr, array $vars)
    {
        $parts = explode('.', $expr);
        $val = $vars;
        foreach ($parts as $part) {
            // Appel de méthode sans argument, ex : getId()
            if (is_object($val) && preg_match('/^(\w+)\(\)$/', $part, $matches)) {
                $method = $matches[1];
                if (method_exists($val, $method)) {
                    $val = $val->$method();
                    continue;
                } else {
                    return null;
                }
            }
            // Accès à une clé dans un tableau
            if (is_array($val) && array_key_exists($part, $val)) {
                $val = $val[$part];
                continue;
            }
            // Getter magique getXxx() même si propriété protégée
            if (is_object($val)) {
                $getter = 'get' . ucfirst($part);
                if (method_exists($val, $getter)) {
                    $val = $val->$getter();
                    continue;
                }
                // Accès à une propriété publique (rare dans ton cas)
                if (property_exists($val, $part)) {
                    $val = $val->$part;
                    continue;
                }
            }
            // Si rien ne correspond, retourne null
            return null;
        }
        return $val;
    }

    /**
     * Résout le chemin absolu d'un template à inclure ou à hériter.
     * Gère les chemins relatifs, absolus, notation module::template, etc.
     * @param string $relative Chemin relatif ou spécial du template
     * @param string $currentFile Chemin du template courant (pour le relatif)
     * @return string Chemin absolu du template résolu
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
