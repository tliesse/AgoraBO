<?php

/**
 *  AGORA
 * 	©  Logma, 2019
 * @package default
 * @author MD
 * @version    1.0
 * @link       http://www.php.net/manual/fr/book.pdo.php
 * 
 * Classe d'accès aux données. 
 * Utilise les services de la classe PDO
 * pour l'application AGORA
 * Les attributs sont tous statiques,
 * $monPdo de type PDO 
 * $monPdoAgora qui contiendra l'unique instance de la classe
*/

class PdoAgora {
    private static $monPdo;
    private static $monPdoAgora = null;

    /**
     * Constructeur privé, crée l'instance de PDO qui sera sollicitée
     * pour toutes les méthodes de la classe
    */
    private function __construct() {
		try {  
            //! encodage
			$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''); 

            //! Crée un objet PDO qui représente une connexion à la BDD
			PdoAgora::$monPdo = new PDO($_ENV['AGORA_DSN'],$_ENV['AGORA_DB_USER'],$_ENV['AGORA_DB_PWD'], $options);

            // configure l'attribut ATTR_ERRMODE pour définir le mode de rapport d'erreurs
            // PDO::ERRMODE_EXCEPTION: émet une exception   
			PdoAgora::$monPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // configure l'attribut ATTR_DEFAULT_FETCH_MODE pour définir le mode de récupération par défaut
            // PDO::FETCH_OBJ: retourne un objet anonyme avec les noms de propriétés
            // qui correspondent aux noms des colonnes retournés dans le jeu de résultats
			PdoAgora::$monPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		}
		catch (PDOException $e)	{ // $e est un objet de la classe PDOException, il expose la description du problème
			die('<section id="main-content"><section class="wrapper"><div class = "erreur">Erreur de connexion à la base de données !<p>'
				.$e->getmessage().'</p></div></section></section>');
		}
    }
	
    /**
     * Destructeur, supprime l'instance de PDO  
    */
    public function _destruct() {
        PdoAgora::$monPdo = null;
    }

    /**
     * Fonction statique qui crée l'unique instance de la classe
     * Appel : $instancePdoAgora = PdoAgora::getPdoAgora();
     * 
     * @return l'unique objet de la classe PdoAgora
    */
    public static function getPdoAgora() {
        if (PdoAgora::$monPdoAgora == null) {
            PdoAgora::$monPdoAgora = new PdoAgora();
        }
        return PdoAgora::$monPdoAgora;
    }

	//==============================================================================
	//
	//	METHODES POUR LA GESTION DES GENRES
	//
	//==============================================================================
	
    /**
     * Retourne tous les genres sous forme d'un tableau d'objets 
     * @author : Mme DELIO
     * @return array le tableau d'objets  (Genre)
    */
    public function getLesGenres(): array {
        $requete =  'SELECT g.idGenre as identifiant, g.libGenre as libelle, COUNT(j.idGenre) as nbJeux'.
                    ' FROM genre as g'.
                    ' LEFT JOIN jeu_video as j ON g.idGenre = j.idGenre'.
                    ' GROUP BY g.idGenre, g.libGenre'.
                    ' ORDER BY g.libGenre';
		try	{	 
			$resultat = PdoAgora::$monPdo->query($requete);
			$tbGenres  = $resultat->fetchAll();	
			return $tbGenres;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    /**
     * Ajoute un nouveau genre avec le libellé donné en paramètre
     * @author : Mme DELIO
     * @param string $libGenre : le libelle du genre à ajouter
     * @return int l'identifiant du genre crée
    */
    public function ajouterGenre(string $libGenre): int {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("INSERT INTO genre "
                    . "(idGenre, libGenre) "
                    . "VALUES (0, :unLibGenre) ");
            $requete_prepare->bindParam(':unLibGenre', $libGenre, PDO::PARAM_STR);
            $requete_prepare->execute();
			// récupérer l'identifiant crée
			return PdoAgora::$monPdo->lastInsertId(); 
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
        }
    }
	
    /**
     * Modifie le libellé du genre donné en paramètre
     * @author : Mme DELIO
     * @param int $idGenre : l'identifiant du genre à modifier  
     * @param string $libGenre : le libellé modifié
    */
    public function modifierGenre(int $idGenre, string $libGenre): void {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("UPDATE genre "
                    . "SET libGenre = :unLibGenre "
                    . "WHERE genre.idGenre = :unIdGenre");
            $requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unLibGenre', $libGenre, PDO::PARAM_STR);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
        }
    }
	
    /**
     * Supprime le genre donné en paramètre
     * @author : Mme DELIO
     * @param int $idGenre :l'identifiant du genre à supprimer 
    */
    public function supprimerGenre(int $idGenre): void {
       try {
            $requete_prepare = PdoAgora::$monPdo->prepare("DELETE FROM genre "
                    . "WHERE genre.idGenre = :unIdGenre");
            $requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
        }
    }
	
//==============================================================================
//
//	METHODES POUR LA GESTION DES JEUX
//
//==============================================================================

    /**
     * @author : Théo LIESSE
     * @param string $nom : le libelle du nom à ajouter
     * @return string : $refJeu : la référence du jeu crée
     * @param float : $prix : le prix a ajouter
     * @param string : $dateParution : la date de parution a ajouter
     * @param string : $ageLimite : l'âge limite a ajouter
     * @param string : $nomMarque : la marque a ajouter
     * @param string : libGenre : le genre a ajouter
     * @param string : libPlateforme : la plateforme a ajouter
    */
    public function getLesJeux(): array {
        $requete =  'SELECT d.refJeu as identifiant, d.nom as libelle, d.idPlateforme, d.idPegi, d.idGenre, d.idMarque, d.prix, d.dateParution, 
            g.libGenre, g.idGenre , 
            p.libPlateforme, p.idPlateforme, 
            pe.idPegi, pe.ageLimite, pe.descPegi, 
            m.idMarque, m.nomMarque
            FROM jeu_video As d 
            INNER JOIN genre As g ON d.idGenre = g.idGenre 
            INNER JOIN plateforme As p ON d.idPlateforme = p.idPlateforme 
            INNER JOIN pegi As pe ON d.idPegi = pe.idPegi 
            INNER JOIN marque As m ON d.idMarque = m.idMarque
            ORDER BY dateParution';
        try {
            $resultat = PdoAgora::$monPdo->query($requete);
            $tbJeux  = $resultat->fetchAll();
            return $tbJeux;
        } catch (PDOException $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                . $e->getmessage() . '</p></div>');
        }
    }
     
    /**
     * @author : Théo LIESSE
    */
    public function ajouterJeu(string $refJeu, int $idPlateforme, int $idPegi, int $idGenre, int $idMarque, string $nomJeu, float $prixJeu, string $date): string
    {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("INSERT INTO jeu_video "
                . "VALUES (:unrefJeu, :unIdPlateforme,:unIdPegi,:unIdGenre,:unIdMarque,:unNomJeu,:unPrixJeu,:unDateJeu)");
            $requete_prepare->bindParam(':unrefJeu', $refJeu, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unNomJeu', $nomJeu, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unPrixJeu', $prixJeu, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unDateJeu', $date, PDO::PARAM_STR);
            $requete_prepare->execute();
            return $refJeu;
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                . $e->getmessage() . '</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function modifierJeu(string $refJeu, int $idPlateforme, int $idPegi, int $idGenre, int $idMarque, string $nomJeu, float $prixJeu, string $date): void
    {
        echo $refJeu;
        try {
            $requete = "UPDATE jeu_video "
                . "SET idPlateforme = :unIdPlateforme, "
                . "idPegi = :unIdPegi, "
                . "idGenre = :unIdGenre, "
                . "idMarque = :unIdMarque, "
                . "nom = :unNomJeu, "
                . "prix = :unPrixJeu, "
                . "dateParution = :unDateJeu "
                . "WHERE refJeu = :unrefJeu";
            $requete_prepare = PdoAgora::$monPdo->prepare($requete);
            $requete_prepare->bindParam(':unrefJeu', $refJeu, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdGenre', $idGenre, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unNomJeu', $nomJeu, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unPrixJeu', $prixJeu, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unDateJeu', $date, PDO::PARAM_STR);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                . $e->getmessage() . '</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function supprimerJeu(string $refJeu): void {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("DELETE FROM jeu_video "
                    . "WHERE jeu_video.refJeu = :unRefJeu");
            $requete_prepare->bindParam(':unRefJeu', $refJeu, PDO::PARAM_STR);
            $requete_prepare->execute();
        } 
        catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

//==============================================================================
//
//	METHODES POUR LA GESTION DES PLATEFORMES
//
//==============================================================================

    /**
     * @author : Théo LIESSE
     * @param string $libPlateforme : le libelle de la plateforme
     * @param int $idPlateforme : l'identifiant de la plateforme
    */
    public function getLesPlateformes(): array {
        $requete =  'SELECT p.idPlateforme as identifiant, p.libPlateforme as libelle, COUNT(j.idPlateforme) as nbJeux'.
                    ' FROM plateforme as p'.
                    ' LEFT JOIN jeu_video as j ON p.idPlateforme = j.idPlateforme'.
                    ' GROUP BY p.idPlateforme, p.libPlateforme'.
                    ' ORDER BY p.libPlateforme';
    try {    
        $resultat = PdoAgora::$monPdo->query($requete);
        $tbPlateformes  = $resultat->fetchAll();  
        return $tbPlateformes;    
        }
    catch (PDOException $e) {  
        die('<div class = "erreur">Erreur dans la requête !<p>'
            .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function ajouterPlateforme(string $libPlateforme): int {
    try {
        $requete_prepare = PdoAgora::$monPdo->prepare("INSERT INTO plateforme "
                . "(idPlateforme, libPlateforme) "
                . "VALUES (0, :unLibPlateforme) ");
        $requete_prepare->bindParam(':unLibPlateforme', $libPlateforme, PDO::PARAM_STR);
        $requete_prepare->execute();
        // récupérer l'identifiant crée
        return PdoAgora::$monPdo->lastInsertId();
        } 
    catch (Exception $e) {
        die('<div class = "erreur">Erreur dans la requête !<p>'
            .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function modifierPlateforme(int $idPlateforme, string $libPlateforme): void {
    try {
        $requete_prepare = PdoAgora::$monPdo->prepare("UPDATE plateforme "
                . "SET libPlateforme = :unLibPlateforme "
                . "WHERE plateforme.idPlateforme = :unIdPlateforme");
        $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
        $requete_prepare->bindParam(':unLibPlateforme', $libPlateforme, PDO::PARAM_STR);
        $requete_prepare->execute();
        } 
    catch (Exception $e) {
        die('<div class = "erreur">Erreur dans la requête !<p>'
            .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function supprimerPlateforme(int $idPlateforme): void {
    try {
        $requete_prepare = PdoAgora::$monPdo->prepare("DELETE FROM plateforme "
                . "WHERE plateforme.idPlateforme = :unIdPlateforme");
        $requete_prepare->bindParam(':unIdPlateforme', $idPlateforme, PDO::PARAM_INT);
        $requete_prepare->execute();
        } 
    catch (Exception $e) {
        die('<div class = "erreur">Erreur dans la requête !<p>'
            .$e->getmessage().'</p></div>');
        }
    }
 
//==============================================================================
//
//	METHODES POUR LA GESTION DES MARQUES
//
//==============================================================================

    /**
     * @author : Théo LIESSE
     * @param string $nomMarque : le libelle de la marque
     * @param int $idMarque : l'identifiant de la marque
    */
    public function getLesMarques(): array {
        $requete =  'SELECT m.idMarque as identifiant, m.nomMarque as libelle, COUNT(j.idMarque) as nbJeux'.
                    ' FROM marque as m'.
                    ' LEFT JOIN jeu_video as j ON m.idMarque = j.idMarque'.
                    ' GROUP BY m.idMarque, m.nomMarque'.
                    ' ORDER BY m.nomMarque';
		try	{	 
			$resultat = PdoAgora::$monPdo->query($requete);
			$tbMarques  = $resultat->fetchAll();	
			return $tbMarques;		
		}
		catch (PDOException $e)	{  
			die('<div class = "erreur">Erreur dans la requête !<p>'
				.$e->getmessage().'</p></div>');
		}
    }

    /**
     * @author : Théo LIESSE
    */
    public function ajouterMarque(string $nomMarque): int {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("INSERT INTO marque "
                    . "(idMarque, nomMarque) "
                    . "VALUES (0, :unNomMarque) ");
            $requete_prepare->bindParam(':unNomMarque', $nomMarque, PDO::PARAM_STR);
            $requete_prepare->execute();

            // récupérer l'identifiant crée
            return PdoAgora::$monPdo->lastInsertId(); 
        } 
        catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }  
    }

    /**
     * @author : Théo LIESSE
    */
    public function modifierMarque(int $idMarque, string $nomMarque): void {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("UPDATE marque "
                    . "SET nomMarque = :unNomMarque "
                    . "WHERE marque.idMarque = :unIdMarque");
            $requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unNomMarque', $nomMarque, PDO::PARAM_STR);
            $requete_prepare->execute();
        } 
        catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function supprimerMarque(int $idMarque): void {
        try {
             $requete_prepare = PdoAgora::$monPdo->prepare("DELETE FROM marque "
                     . "WHERE marque.idMarque = :unIdMarque");
             $requete_prepare->bindParam(':unIdMarque', $idMarque, PDO::PARAM_INT);
             $requete_prepare->execute();
         } 
         catch (Exception $e) {
             die('<div class = "erreur">Erreur dans la requête !<p>'
                 .$e->getmessage().'</p></div>');
         }
     }
     
//==============================================================================
//
//	METHODES POUR LA GESTION DES PEGIS
//
//==============================================================================

    /**
     * @author : Théo LIESSE
     * @param string $ageLimite : le libelle du pegi
     * @param int $idPegi : l'identifiant du pegi
     * @param string $descPegi : la description du pegi
    */
    public function getLesPegis(): array {
        $requete =  'SELECT p.idPegi as identifiant, p.ageLimite as libelle, p.descPegi as description, COUNT(j.idPegi) as nbJeux'.
            ' FROM pegi as p'.
            ' LEFT JOIN jeu_video as j ON p.idPegi = j.idPegi'.
            ' GROUP BY p.idPegi, p.ageLimite'.
            ' ORDER BY p.ageLimite';
        try	{	 
            $resultat = PdoAgora::$monPdo->query($requete);
            $tbPegi  = $resultat->fetchAll();	
            return $tbPegi;		
        }
        catch (PDOException $e)	{  
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function ajouterPegi(int $ageLimite, $descPegi): string {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("INSERT INTO pegi "
                    . "(idPegi, ageLimite, descPegi) "
                    . "VALUES (0, :unAgeLimite, :unDescPegi) ");
            $requete_prepare->bindParam(':unAgeLimite', $ageLimite, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unDescPegi', $descPegi, PDO::PARAM_STR);
            $requete_prepare->execute();
            // récupérer l'identifiant crée
            return PdoAgora::$monPdo->lastInsertId(); 
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        } 
    }

    /**
     * @author : Théo LIESSE
    */
    public function modifierPegi(int $idPegi, string $ageLimite, string $descPegi): void {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare("UPDATE pegi "
                    . "SET ageLimite = :unAgeLimite ,"
                    . "descPegi = :unDescPegi "
                    . "WHERE pegi.idPegi = :unIdPegi");
            $requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
            $requete_prepare->bindParam(':unAgeLimite', $ageLimite, PDO::PARAM_STR);
            $requete_prepare->bindParam(':unDescPegi', $descPegi, PDO::PARAM_STR);
            $requete_prepare->execute();
        } catch (Exception $e) {
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function supprimerPegi(int $idPegi): void {
        try {
                $requete_prepare = PdoAgora::$monPdo->prepare("DELETE FROM pegi "
                        . "WHERE pegi.idPegi = :unIdPegi");
                $requete_prepare->bindParam(':unIdPegi', $idPegi, PDO::PARAM_INT);
                $requete_prepare->execute();
            } catch (Exception $e) {
                die('<div class = "erreur">Erreur dans la requête !<p>'
                    .$e->getmessage().'</p></div>');
        }
    }

//==============================================================================
//
// METHODES POUR LA GESTION DES MEMBRES
//
//==============================================================================
 
    /** Retourne l'identifiant, nom et prénom de l'user coresspondant au compte et mdp
     * 
     * @param string $loginMembre = le compte de l'user
     * @param string $mdpMembre = le mdp de l'user
     * @return ?object = l'objet ou null si ce membre n'existe pas
    */
    public function getUnMembre(string $loginMembre, string $mdpMembre): ?object {
        try {
            $requete_prepare = PdoAgora::$monPdo->prepare(
                'SELECT idMembre, prenomMembre, nomMembre, mdpMembre, selMembre
                    FROM membre
                    WHERE loginMembre = :leLoginMembre'); 
            $requete_prepare->bindValue(':leLoginMembre', $loginMembre, PDO::PARAM_STR);
            $requete_prepare->execute();
        
            if($utilisateur = $requete_prepare->fetch()){
                $mdpHash = hash('SHA512', $mdpMembre . $utilisateur->selMembre);
            if($mdpHash == $utilisateur->mdpMembre) {
                return $utilisateur;
            } else {
                return null;
                }
            }
        }   
        catch (Exception $e) {
                die('<div class="erreur">Erreur dans la requête !<p>'
                .$e->getMessage().'</p></div');
        }
    }

    public function getLesMembres(): array {
        $requete =  'SELECT idMembre as identifiant, nomMembre as libelle
                    FROM membre';
        try	{	 
            $resultat = PdoAgora::$monPdo->query($requete);
            $tbMembres  = $resultat->fetchAll();	
            return $tbMembres;		
        }
        catch (PDOException $e) {  
                die('<div class = "erreur">Erreur dans la requête !<p>'
                    .$e->getmessage().'</p></div>');
        }
    }
}
?>