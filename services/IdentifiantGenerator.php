<?php
// services/IdentifiantGenerator.php

// Cette classe dépend de l'objet PDO ($pdo) pour interagir avec la base de données.
// Elle supposera que $pdo est disponible globalement.
// Si $pdo n'est pas global, la façon la plus propre serait de passer $pdo en argument
// à la méthode generateId ou à un constructeur si la classe était instanciée.
// Pour l'instant, nous utilisons 'global $pdo;' pour la simplicité avec votre configuration actuelle.

class IdentifiantGenerator {

    /**
     * Génère un identifiant unique basé sur un préfixe et l'année en cours.
     * Le format est PREFIXE-ANNEE-SEQUENCE (ex: RAP-2025-0015).
     * Utilise la table 'sequences' pour maintenir les compteurs annuels.
     *
     * @param string $prefixe Le code de trois lettres majuscules identifiant l'entité (ex: 'RAP', 'ETU', 'AUDIT').
     * @param int $annee L'année pour laquelle générer l'ID (ex: 2025).
     * @return string L'identifiant unique formaté.
     * @throws Exception Si la génération de l'ID échoue (par exemple, problème de base de données).
     */
    public static function generateId(string $prefixe, int $annee): string {
        global $pdo; // Accéder à l'objet PDO global.

        // Vérification de l'objet PDO
        if (!$pdo) {
            // Ceci devrait indiquer un problème de configuration de la base de données
            throw new Exception("Erreur de configuration : L'objet PDO n'est pas disponible pour la génération d'ID.");
        }

        try {
            // Démarre une transaction pour assurer l'atomicité de l'opération
            $pdo->beginTransaction();

            // 1. Verrouiller la ligne de la séquence pour éviter les conditions de concurrence (race conditions)
            // SELECT ... FOR UPDATE bloque la ligne pour d'autres transactions jusqu'à ce que la transaction actuelle soit terminée.
            $stmt = $pdo->prepare("SELECT valeur_actuelle FROM sequences WHERE nom_sequence = :prefixe AND annee = :annee FOR UPDATE");
            $stmt->execute([':prefixe' => $prefixe, ':annee' => $annee]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $nouvelle_valeur = 1;
            if ($result) {
                // Si la séquence existe déjà pour cette année/préfixe, on l'incrémente
                $nouvelle_valeur = $result['valeur_actuelle'] + 1;
                $stmt_update = $pdo->prepare("UPDATE sequences SET valeur_actuelle = :valeur WHERE nom_sequence = :prefixe AND annee = :annee");
                $stmt_update->execute([
                    ':valeur' => $nouvelle_valeur,
                    ':prefixe' => $prefixe,
                    ':annee' => $annee
                ]);
            } else {
                // Si la séquence n'existe pas, c'est le premier ID de ce type pour cette année
                $stmt_insert = $pdo->prepare("INSERT INTO sequences (nom_sequence, annee, valeur_actuelle) VALUES (:prefixe, :annee, 1)");
                $stmt_insert->execute([
                    ':prefixe' => $prefixe,
                    ':annee' => $annee
                ]);
            }

            // Valide la transaction, libérant le verrou sur la ligne de séquence
            $pdo->commit();

            // 2. Formater la séquence (ex: 0001, 0015, 0128)
            // sprintf('%04d', ...) formate le nombre avec des zéros à gauche pour avoir 4 chiffres.
            $sequence_formatee = sprintf('%04d', $nouvelle_valeur);

            // 3. Assembler l'identifiant final
            return strtoupper($prefixe) . '-' . $annee . '-' . $sequence_formatee;

        } catch (PDOException $e) {
            // En cas d'erreur de base de données, annuler la transaction
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Relancer une exception avec un message plus détaillé
            throw new Exception("Erreur DB lors de la génération de l'ID pour " . $prefixe . "-" . $annee . " : " . $e->getMessage());
        } catch (Exception $e) {
            // Gérer toute autre exception inattendue
            throw new Exception("Erreur inattendue lors de la génération de l'ID : " . $e->getMessage());
        }
    }
}
// TRÈS IMPORTANT : Pas de balise de fermeture '?>' à la fin de ce fichier.
// Cela élimine le risque d'espaces ou de sauts de ligne accidentels après la fermeture,
// qui pourraient corrompre la sortie JSON d'autres scripts.