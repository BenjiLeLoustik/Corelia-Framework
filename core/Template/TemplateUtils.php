<?php

/* ===== /Core/Template/TemplateUtils.php ===== */

namespace Corelia\Template;

/**
 * Classe utilitaire statique pour le parsing, la compilation et la résolution des variables dans les templates.
 * Fournit toutes les méthodes nécessaires pour transformer la syntaxe Twig-like en PHP natif,
 * gérer les blocs, les boucles, les conditions, les includes, et la résolution des chemins.
 */
class TemplateUtils
{
    /**
     * Convertit une expression Twig en PHP natif.
     * @param string $expr Expression Twig à convertir.
     * @param bool $isStatement Indique si c'est une instruction (for, if, set...).
     * @return string Expression PHP équivalente.
     */
    public static function twigToPhp(string $expr, bool $isStatement = false): string
    {
        // Remplacements de base pour les opérateurs Twig
        $expr = preg_replace('/\bnot\b/', '!', $expr);
        $expr = preg_replace('/\band\b/', '&&', $expr);
        $expr = preg_replace('/\bor\b/', '||', $expr);

        // Gestion des instructions (for, if, set, etc.)
        if ($isStatement) {
            // Boucle for
            if (preg_match('/^for (\w+)(?:,\s*(\w+))? in ([^\s]+)$/', $expr, $m)) {
                $v = $m[1];
                $k = $m[2] ?? null;
                $arr = $m[3];
                if ($k) {
                    return "foreach ({$arr} as \${$v} => \${$k}) :";
                } else {
                    return "foreach ({$arr} as \${$v}) :";
                }
            }
            // Fin de boucle/condition
            if (preg_match('/^end(for|if)$/', $expr, $m)) {
                return "endforeach;";
            }
            // Condition if
            if (preg_match('/^if (.+)$/', $expr, $m)) {
                return "if ({$m[1]}) :";
            }
            // Condition elseif
            if (preg_match('/^elseif (.+)$/', $expr, $m)) {
                return "elseif ({$m[1]}) :";
            }
            // Bloc else
            if (trim($expr) === 'else') {
                return "else :";
            }
            // Fin de condition
            if (trim($expr) === 'endif') {
                return "endif;";
            }
            // Affectation de variable
            if (preg_match('/^set (\w+) = (.+)$/', $expr, $m)) {
                return "\${$m[1]} = {$m[2]};";
            }
        }
        // Expression simple
        return $expr;
    }

    /**
     * Récupère récursivement toutes les dépendances (extends, includes) d'un template.
     * @param string $templatePath Chemin du template principal.
     * @param array &$dependencies Liste des dépendances (rempli récursivement).
     * @return array Liste complète des dépendances (chemins absolus).
     */
    public static function getTemplateDependencies(string $templatePath, array &$dependencies = []): array
    {
        $realPath = realpath($templatePath);
        if (!$realPath || in_array($realPath, $dependencies)) return $dependencies;
        $dependencies[] = $realPath;

        $content = @file_get_contents($realPath);
        if (!$content) return $dependencies;

        // Dépendance parent (extends)
        if (preg_match('/\{% extends [\'"]([^\'"]+)[\'"] %\}/', $content, $match)) {
            $parentPath = self::resolvePath($match[1], $realPath);
            self::getTemplateDependencies($parentPath, $dependencies);
        }
        // Dépendances includes
        if (preg_match_all('/\{% include [\'"]([^\'"]+)[\'"] %\}/', $content, $matches)) {
            foreach ($matches[1] as $inc) {
                $incPath = self::resolvePath($inc, $realPath);
                self::getTemplateDependencies($incPath, $dependencies);
            }
        }
        return $dependencies;
    }

    /**
     * Parse toutes les instructions du template (set, include, for, if...).
     * @param string $tpl Contenu du template à parser.
     * @param array &$vars Variables du contexte (passées par référence).
     * @param string $file Chemin du template courant (pour les includes).
     * @return string Template transformé.
     */
    public static function parseAll(string $tpl, array &$vars, string $file): string
    {
        // Suppression des commentaires Twig {# ... #}
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
                $resolved = self::resolveTwigVar($value, $vars);
                $vars[$name] = $resolved !== null ? $resolved : $value;
            }
            return '';
        }, $tpl);

        // Gestion des includes {% include 'file' %}
        $tpl = preg_replace_callback('/\{% include [\'"]([^\'"]+)[\'"] %\}/', function ($m) use ($vars, $file) {
            $incPath = self::resolvePath($m[1], $file);
            $parser = new TemplateParser();
            return $parser->renderTemplate($incPath, $vars, []);
        }, $tpl);

        // Boucles for imbriquées
        $tpl = self::parseForBlocks($tpl, $vars, $file);

        // Conditions imbriquées
        $tpl = self::parseIfBlocks($tpl, $vars, $file);

        // Remplacement des variables et filtres
        $tpl = self::parseString($tpl, $vars);

        return $tpl;
    }

    /**
     * Parse récursivement les blocs for imbriqués.
     * Gère {% for ... in ... %} ... {% endfor %} et {% for key, value in array %}
     * @param string $tpl Contenu du template à parser.
     * @param array &$vars Variables du contexte (passées par référence).
     * @param string $file Chemin du template courant.
     * @return string Template transformé avec les boucles déroulées.
     */
    public static function parseForBlocks(string $tpl, array &$vars, string $file): string
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
                            $arr = self::resolveTwigVar($arrExpr, $vars);
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
                                    $out .= self::parseString(self::parseAll($block, $localVars, $file), $localVars);
                                    $i++;
                                }
                            }
                            $tpl = substr($tpl, 0, $start) . $out . $after;
                            return self::parseForBlocks($tpl, $vars, $file);
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
     * @param string $tpl Contenu du template à parser.
     * @param array &$vars Variables du contexte (passées par référence).
     * @param string $file Chemin du template courant.
     * @return string Template transformé avec conditions évaluées.
     */
    public static function parseIfBlocks(string $tpl, array &$vars, string $file): string
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
                    $result = self::parseAll($part['block'], $vars, $file);
                    break;
                }
                $expr = $part['cond'];
                if (preg_match('/^((?:[\w\.]+)|(?:-?\d+)|(?:["\'][^"\']+["\']))\s*(==|!=)\s*((?:[\w\.]+)|(?:-?\d+)|(?:["\'][^"\']+["\']))\s*$/', $expr, $cmp)) {
                    $leftRaw = trim($cmp[1]);
                    $op = trim($cmp[2]);
                    $rightRaw = trim($cmp[3]);
                    $left = self::parseIfOperand($leftRaw, $vars);
                    $right = self::parseIfOperand($rightRaw, $vars);
                    $ok = ($op === '==') ? ($left == $right) : ($left != $right);
                    if ($ok) {
                        $result = self::parseAll($part['block'], $vars, $file);
                        break;
                    }
                } else {
                    $value = self::resolveTwigVar($expr, $vars);
                    if ($value) {
                        $result = self::parseAll($part['block'], $vars, $file);
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
     * @param string $operand Expression à analyser.
     * @param array $vars Variables du contexte.
     * @return mixed Valeur évaluée.
     */
    public static function parseIfOperand(string $operand, array $vars)
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
            return self::resolveTwigVar($operand, $vars);
        }
    }

    /**
     * Remplace les expressions {{ ... }} dans le template par leur valeur.
     * Gère les filtres de base (upper, lower, date, raw), l'accès aux tableaux, et le debug.
     * @param string $tpl Contenu du template à parser.
     * @param array $vars Variables du contexte.
     * @return string Template transformé avec les variables remplacées.
     */
    public static function parseString(string $tpl, array $vars): string
    {
        // Debug : {{ dump(variable) }}
        $tpl = preg_replace_callback('/\{\{\s*dump\((.*?)\)\s*\}\}/', function($m) use ($vars) {
            $expr = trim($m[1]);
            $val = null;
            if (preg_match('/^["\'](.+)["\']$/', $expr, $mm)) {
                $val = $mm[1];
            } else {
                $val = self::resolveTwigVar($expr, $vars);
            }
            ob_start();
            echo '<pre style="background:#222;color:#eee;padding:10px;border-radius:6px;font-size:13px">';
            var_dump($val);
            echo '</pre>';
            return ob_get_clean();
        }, $tpl);

        // Accès tableau : {{ tableau[clé] }}
        $tpl = preg_replace_callback('/\{\{\s*([\w\.]+)\[([^\]\}]+)\]\s*\}\}/', function($m) use ($vars) {
            $array = self::resolveTwigVar($m[1], $vars);
            $keyExpr = trim($m[2]);
            if (preg_match('/^["\'](.+)["\']$/', $keyExpr, $mm)) {
                $key = $mm[1];
            } else {
                $key = self::resolveTwigVar($keyExpr, $vars);
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
                    $val = self::resolveTwigVar($m[3], $vars);
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
     * @param string $expr Expression à résoudre (ex : foo.bar, foo.getName()).
     * @param array $vars Variables du contexte.
     * @return mixed|null Valeur trouvée ou null si non trouvée.
     */
    public static function resolveTwigVar(string $expr, array $vars)
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
                if (property_exists($val, $part)) {
                    $val = $val->$part;
                    continue;
                }
            }
            return null;
        }
        return $val;
    }

    /**
     * Résout le chemin absolu d'un template à inclure ou à hériter.
     * Gère les chemins relatifs, absolus, notation module::template, etc.
     * @param string $relative Chemin relatif ou spécial du template.
     * @param string $currentFile Chemin du template courant (pour le relatif).
     * @return string Chemin absolu du template résolu.
     */
    public static function resolvePath(string $relative, string $currentFile): string
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
