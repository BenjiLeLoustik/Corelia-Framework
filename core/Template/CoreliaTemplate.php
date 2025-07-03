<?php

namespace Corelia\Template;

/**
 * Moteur de template avancé "like Twig" pour CoreliaPHP.
 * Prend en charge :
 *  - Variables et accès pointé ({{ var }}, {{ var.prop }})
 *  - Accès tableau dynamique ({{ tableau[clé] }})
 *  - Boucles for simples et clé/valeur ({% for key, value in arr %})
 *  - Conditions avancées avec if/elseif/else/endif et comparaisons ==, !=, in, not in
 *  - Blocs, extends, includes
 *  - Filtres de base (upper, lower, date, raw)
 *  - Définition de variables avec {% set var = ... %} (support JSON, guillemets simples/doubles)
 *  - Commentaires Twig {# ... #}
 *  - Variable spéciale loop dans les boucles for (loop.first, loop.last, loop.index, etc.)
 */
class CoreliaTemplate
{
    protected string $templatePath;
    protected array $blocks = [];
    protected array $blockStack = [];
    protected ?string $parentTemplate = null;

    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
    }

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
                $vars[$name] = $value;
            }
            return '';
        }, $tpl);

        // {% include 'file' %}
        $tpl = preg_replace_callback('/\{% include [\'"]([^\'"]+)[\'"] %\}/', function ($m) use ($vars, $file) {
            $incPath = $this->resolvePath($m[1], $file);
            return $this->renderTemplate($incPath, $vars, $this->blocks);
        }, $tpl);

        // if / elseif / else / endif with ==, !=, in, not in
        $tpl = preg_replace_callback(
            '/\{% if ([^%]+) %\}(.*?)((?:\{% elseif [^%]+ %\}.*?)*)(?:\{% else %\}(.*?))?\{% endif %\}/s',
            function ($m) use ($vars, $file) {
                $conditions = [
                    ['cond' => trim($m[1]), 'block' => $m[2]]
                ];
                if (!empty($m[3])) {
                    preg_match_all('/\{% elseif ([^%]+) %\}(.*?)(?=(\{% elseif [^%]+ %\}|\{% else %\}|\{% endif %\}))/s', $m[3], $matches, PREG_SET_ORDER);
                    foreach ($matches as $elseif) {
                        $conditions[] = ['cond' => trim($elseif[1]), 'block' => $elseif[2]];
                    }
                }
                foreach ($conditions as $cond) {
                    $expr = $cond['cond'];
                    // == et !=
                    if (preg_match('/^([\w\.]+)\s*(==|!=)\s*(["\']?.+?["\']?)$/', $expr, $cmp)) {
                        $left = $this->resolveTwigVar(trim($cmp[1]), $vars);
                        $op = trim($cmp[2]);
                        $rightRaw = trim($cmp[3]);
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
                        if ($ok) return $cond['block'];
                    }
                    // in / not in
                    elseif (preg_match('/^([\w\.]+)\s+(not\s+in|in)\s+(\[.+\])$/', $expr, $cmp)) {
                        $left = $this->resolveTwigVar(trim($cmp[1]), $vars);
                        $op = trim($cmp[2]);
                        $rightRaw = trim($cmp[3]);
                        // Supporte ['a','b'] ou ["a","b"] ou mixte
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
                        if ($ok) return $cond['block'];
                    } else {
                        $value = $this->resolveTwigVar($expr, $vars);
                        if ($value) return $cond['block'];
                    }
                }
                return $m[4] ?? '';
            },
            $tpl
        );

        // for loops avec variable loop
        $tpl = preg_replace_callback('/\{% for (\w+)(?:,\s*(\w+))? in ([\w\.]+) %\}(.*?)\{% endfor %\}/s', function ($m) use ($vars, $file) {
            $keyVar = isset($m[2]) && $m[2] ? $m[1] : null;
            $valVar = isset($m[2]) && $m[2] ? $m[2] : $m[1];
            $arr = $this->resolveTwigVar($m[3], $vars);
            $out = '';
            if (is_array($arr)) {
                $i = 0;
                $len = count($arr);
                foreach ($arr as $k => $v) {
                    $localVars = $vars;
                    if ($keyVar !== null) {
                        $localVars[$keyVar] = $k;
                        $localVars[$valVar] = $v;
                    } else {
                        $localVars[$valVar] = $v;
                    }
                    // Ajout de la variable spéciale loop
                    $localVars['loop'] = [
                        'index' => $i + 1,
                        'index0' => $i,
                        'revindex' => $len - $i,
                        'revindex0' => $len - $i - 1,
                        'first' => $i === 0,
                        'last' => $i === $len - 1,
                        'length' => $len,
                    ];
                    $out .= $this->parseString($this->parseAll($m[4], $localVars, $file), $localVars);
                    $i++;
                }
            }
            return $out;
        }, $tpl);

        return $tpl;
    }

    protected function parseString(string $tpl, array $vars): string
    {
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

    protected function resolveTwigVar(string $expr, array $vars)
    {
        $parts = explode('.', $expr);
        $val = $vars;
        foreach ($parts as $part) {
            if (is_array($val) && isset($val[$part])) $val = $val[$part];
            elseif (is_object($val) && isset($val->$part)) $val = $val->$part;
            else return null;
        }
        return $val;
    }

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
