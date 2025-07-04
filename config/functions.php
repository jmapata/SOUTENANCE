<?php
// /config/functions.php

// Inclure la connexion PDO pour que les fonctions puissent l'utiliser
require_once 'db_connect.php';

/**
 * Redirige l'utilisateur vers une URL spécifiée.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * [cite_start]Vérifie si l'utilisateur a la permission d'effectuer un traitement. [cite: 265]
 */
function checkPermission($requiredTraitement) {
    global $p_pdo;

    // Si l'utilisateur n'est pas connecté ou n'a pas de groupe, il n'a aucune permission
    if (!isset($_SESSION['user_data']['id_groupe_utilisateur'])) {
        return false;
    }

    $id_groupe = $_SESSION['user_data']['id_groupe_utilisateur'];

    $sql = "SELECT COUNT(*) FROM rattacher WHERE id_groupe_utilisateur = :id_groupe AND id_traitement = :id_traitement";
    $stmt = $p_pdo->prepare($sql);
    $stmt->execute(['id_groupe' => $id_groupe, 'id_traitement' => $requiredTraitement]);

    return $stmt->fetchColumn() > 0;
}


/**
 * [cite_start]Génère un identifiant unique basé sur un préfixe et l'année en cours. [cite: 246-250]
 * Gère la concurrence en utilisant une transaction.
 */
function generateUniqueID($prefix) {
    global $p_pdo;
    $year = date('Y');
    
    try {
        $p_pdo->beginTransaction();

        [cite_start]// Verrouille la ligne pour éviter les accès concurrents [cite: 253]
        $stmt = $p_pdo->prepare("SELECT valeur_actuelle FROM sequences WHERE nom_sequence = :prefix AND annee = :year FOR UPDATE");
        $stmt->execute(['prefix' => $prefix, 'year' => $year]);
        $sequence = $stmt->fetch();

        $currentValue = 0;
        if ($sequence) {
            $currentValue = $sequence['valeur_actuelle'];
        } else {
            [cite_start]// Crée la séquence si elle n'existe pas pour l'année en cours [cite: 254]
            $p_pdo->prepare("INSERT INTO sequences (nom_sequence, annee, valeur_actuelle) VALUES (:prefix, :year, 0)")
                  ->execute(['prefix' => $prefix, 'year' => $year]);
        }
        
        // Incrémente la valeur
        $newValue = $currentValue + 1;

        [cite_start]// Met à jour le compteur [cite: 255]
        $stmt = $p_pdo->prepare("UPDATE sequences SET valeur_actuelle = :newValue WHERE nom_sequence = :prefix AND annee = :year");
        $stmt->execute(['newValue' => $newValue, 'prefix' => $prefix, 'year' => $year]);
        
        $p_pdo->commit(); [cite_start]// Valide la transaction [cite: 256]

        [cite_start]// Formate l'identifiant (ex: ETU-2025-0001) [cite: 249]
        return strtoupper($prefix) . '-' . $year . '-' . str_pad($newValue, 4, '0', STR_PAD_LEFT);

    } catch (Exception $e) {
        $p_pdo->rollBack();
        // Gérer l'erreur, par exemple, la logger
        error_log("Erreur de génération d'ID : " . $e->getMessage());
        return null;
    }
}

/**
 * [cite_start]Enregistre une action de l'utilisateur dans le journal d'audit. [cite: 273]
 */
function logAction($userId, $actionId, $details = []) {
    global $p_pdo;

    $sql = "INSERT INTO enregistrer (id_enregistrement, numero_utilisateur, id_action, date_action, adresse_ip, user_agent, details_action) 
            VALUES (:id_enregistrement, :user_id, :action_id, NOW(), :ip, :user_agent, :details)";
    
    $stmt = $p_pdo->prepare($sql);
    $stmt->execute([
        'id_enregistrement' => generateUniqueID('ENR'), // Utilise le générateur d'ID pour la clé primaire
        'user_id' => $userId,
        'action_id' => $actionId,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
        'details' => json_encode($details)
    ]);
}