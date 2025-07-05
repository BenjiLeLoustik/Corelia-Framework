<?php

/* ===== /Core/CLI/Commands/CacheClearCommand.php ===== */

namespace Corelia\CLI\Commands;

use Corelia\CLI\CommandInterface;

/**
 * Commande CLI pour vider tous les caches de l'application Corelia.
 * Actuellement, supprime le cache des templates compilés.
 * Peut être étendue pour gérer d'autres types de cache (sessions, doctrine, etc.).
 */
class CacheClearCommand implements CommandInterface
{
    /**
     * Retourne le nom de la commande CLI.
     * Exemple d'utilisation : php corelia cache:clear
     *
     * @return string Nom de la commande
     */
    public function getName(): string
    {
        return 'cache:clear';
    }

    /**
     * Retourne la description de la commande, affichée dans l'aide CLI.
     *
     * @return string Description de la commande
     */
    public function getDescription(): string
    {
        return 'Vide tous les caches de l\'application (templates, sessions, etc.)';
    }

    /**
     * Retourne l'aide détaillée de la commande.
     * 
     * Cette méthode permet d'afficher une documentation complète lorsque
     * l'utilisateur ajoute --help ou -h à la commande.
     *
     * @return string Texte d'aide détaillé
     */
    public function getHelp(): string
    {
        return  <<<TXT
                Commande: cache:clear

                Description :
                    Vide tous les caches de l'application Corelia (templates, sessions, etc.)
                    Par défaut, supprime le cache des templates compilés.

                Utilisation :
                    php corelia cache:clear

                Exemples :
                    php corelia cache:clear
                TXT;
    }

    /**
     * Exécute la commande de nettoyage du cache.
     * Supprime récursivement le dossier du cache des templates.
     * Affiche un message de succès ou d'erreur selon le résultat.
     *
     * @param array $argv Arguments de la ligne de commande
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $argv): int
    {
        $ok = true;

        // 1. Nettoyage du cache des templates compilés
        $templateCacheDir = dirname(__DIR__, 3) . '/var/cache/templates';
        $ok = $this->clearDir($templateCacheDir) && $ok;
        echo "[OK] Cache des templates vidé.\n";

        // 2. Ajoute ici d'autres types de cache si besoin (sessions, doctrine, etc.)
        // Exemple :
        // $sessionCacheDir = dirname(__DIR__, 3) . '/var/cache/sessions';
        // $ok = $this->clearDir($sessionCacheDir) && $ok;
        // echo "[OK] Cache des sessions vidé.\n";

        if ($ok) {
            echo "Tous les caches ont été vidés avec succès.\n";
            return 0;
        } else {
            echo "Erreur lors du nettoyage de certains caches.\n";
            return 1;
        }
    }

    /**
     * Supprime récursivement le contenu d'un dossier et le dossier lui-même.
     * Ignore les fichiers spéciaux '.' et '..'.
     *
     * @param string $dir Chemin du dossier à supprimer
     * @return bool true si le dossier est supprimé ou inexistant, false en cas d'erreur
     */
    private function clearDir($dir): bool
    {
        if (!is_dir($dir)) return true;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->clearDir($path);
            } else {
                @unlink($path);
            }
        }
        // Supprime le dossier lui-même à la fin
        return @rmdir($dir);
    }
}
