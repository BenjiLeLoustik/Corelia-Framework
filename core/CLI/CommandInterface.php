<?php

/* ===== /Core/CLI/CommandInterface.php ===== */

namespace Corelia\CLI;

/**
 * Interface de base pour toutes les commandes CLI Corelia.
 *
 * Toute commande CLI doit implémenter cette interface pour être reconnue
 * et utilisée par le système de console Corelia.
 * 
 * Les méthodes permettent de décrire la commande, de fournir une aide détaillée,
 * et d'exécuter la logique de la commande avec les arguments du terminal.
 */
interface CommandInterface
{
    /**
     * Retourne le nom unique de la commande CLI.
     *
     * Ce nom permet d'appeler la commande via le terminal.
     * Exemple : 'cache:clear', 'serve', 'workspace:make'
     *
     * @return string Nom de la commande (utilisé en ligne de commande)
     */
    public function getName(): string;

    /**
     * Retourne une description courte de la commande.
     *
     * Cette description est affichée dans la liste des commandes disponibles
     * (ex : lors de l'appel à 'php corelia help').
     *
     * @return string Description courte de la commande
     */
    public function getDescription(): string;

    /**
     * Retourne l'aide détaillée de la commande.
     *
     * Cette aide est affichée lorsque l'utilisateur utilise --help ou -h
     * ou via la commande 'php corelia help <commande>'.
     * Elle doit contenir l'usage, les options, des exemples et des notes.
     *
     * @return string Texte d'aide détaillé pour l'utilisateur
     */
    public function getHelp(): string;

    /**
     * Exécute la logique principale de la commande.
     *
     * Cette méthode reçoit les arguments du terminal (array $argv)
     * et doit retourner un code de sortie standard :
     *   0 = succès, 1 = erreur ou échec
     *
     * @param array $argv Arguments de la ligne de commande
     * @return int Code de sortie (0 = succès, 1 = erreur)
     */
    public function execute(array $argv): int;
}
