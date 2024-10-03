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
        $requete =  'SELECT refJeu as reference, nom as nom, ageLimite as pegi, nomMarque as marque, libGenre as genre, libPlateforme as plateforme, prix as prix, dateParution as date
                    FROM jeu_video
                    JOIN pegi ON jeu_video.idPegi = pegi.idPegi
                    JOIN marque ON jeu_video.idMarque = marque.idMarque
                    JOIN genre ON jeu_video.idGenre = genre.idGenre
                    JOIN plateforme ON jeu_video.idPlateforme = plateforme.idPlateforme
                    ORDER BY nom';
    try	{	 
        $resultat = PdoAgora::$monPdo->query($requete);
        $tbJeux  = $resultat->fetchAll();
        return $tbJeux;		
        }
    catch (PDOException $e)	{  
        die('<div class = "erreur">Erreur dans la requête !<p>'
            .$e->getmessage().'</p></div>');
        }
    }
     
    /**
     * @author : Théo LIESSE
    */
    public function ajouterJeux(string $refJeu, string $nom, string $ageLimite, string $nomMarque, string $libGenre, string $libPlateforme, float $prix, string $dateParution){
        try {
            $requete = "INSERT INTO jeu_video (refJeu, nom, idPegi, idMarque, idGenre, idPlateforme, prix, dateParution)" 
            ."VALUES (:unRefJeu, :unNom, :unIdPegi, :unIdMarque, :unIdGenre, :unIdPlateforme, :unPrix, :unDateParution); ";
            $requete_prepare = PdoAgora::$monPdo->prepare($requete);
                $requete_prepare->bindParam(':unRefJeu', $refJeu, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unNom', $nom, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdPegi', $ageLimite, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdMarque', $nomMarque, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdGenre', $libGenre, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdPlateforme', $libPlateforme, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unPrix', $prix, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unDateParution', $dateParution, PDO::PARAM_STR);
                $requete_prepare->execute();
                return PdoAgora::$monPdo->lastInsertId();
        } 
        catch (Exception $e) {
            PdoAgora::$monPdo->rollBack();
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getMessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function modifierJeux(string $refJeu, string $nom, string $ageLimite, string $nomMarque, string $libGenre, string $libPlateforme, float $prix, string $dateParution): void {
        try {
            $requete = "UPDATE jeu_video "
                    . "SET refJeu = :unRefJeu, "
                    . "nom = :unNom, "
                    . "idPegi = :unIdPegi, "
                    . "idMarque = :unIdMarque, "
                    . "idGenre = :unIdGenre, "
                    . "idPlateforme = :unIdPlateforme, "
                    . "prix = :unPrix, "
                    . "dateParution = :unDateParution "
                    . "WHERE jeu_video.refJeu = :unRefJeu";
            $requete_prepare = PdoAgora::$monPdo->prepare($requete);
                $requete_prepare->bindParam(':unRefJeu', $refJeu, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unNom', $nom, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdPegi', $ageLimite, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdMarque', $nomMarque, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdGenre', $libGenre, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unIdPlateforme', $libPlateforme, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unPrix', $prix, PDO::PARAM_STR);
                $requete_prepare->bindParam(':unDateParution', $dateParution, PDO::PARAM_STR);
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
    public function supprimerJeux(string $refJeu): void {
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
    public function getLesPlateforme(): array {
        $requete =  'SELECT idPlateforme as identifiant, libPlateforme as libelle
                    FROM plateforme
                    ORDER BY idPlateforme';
    try   {    
        $resultat = PdoAgora::$monPdo->query($requete);
        $tbPlateforme  = $resultat->fetchAll();  
        return $tbPlateforme;    
        }
    catch (PDOException $e) {  
        die('<div class = "erreur">Erreur dans la requête !<p>'
            .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function getLesPlateformeListe(): array {
        $requete =  'SELECT idPlateforme, libPlateforme
                    FROM plateforme
                    ORDER BY idPlateforme';
    try   {    
        $resultat = PdoAgora::$monPdo->query($requete);
        $tbPlateformeListe  = $resultat->fetchAll(PDO::FETCH_OBJ);  
        return $tbPlateformeListe;    
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
    public function getLesMarque(): array {
        $requete =  'SELECT idMarque as identifiant, nomMarque as libelle 
                        FROM marque 
                        ORDER BY nomMarque';
        try	{	 
            $resultat = PdoAgora::$monPdo->query($requete);
            $tbMarque  = $resultat->fetchAll();	
            return $tbMarque;		
        }
        catch (PDOException $e)	{  
            die('<div class = "erreur">Erreur dans la requête !<p>'
                .$e->getmessage().'</p></div>');
        }
    }

    /**
     * @author : Théo LIESSE
    */
    public function getLesMarqueListe(): array {
            $requete =  'SELECT idMarque, nomMarque
                      FROM marque 
                      ORDER BY nomMarque';
            try	{	 
                $resultat = PdoAgora::$monPdo->query($requete);
                $tbMarqueListe  = $resultat->fetchAll(PDO::FETCH_OBJ);	
                return $tbMarqueListe;		
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
    public function getLesPegi(): array {
        $requete =  'SELECT idPegi as identifiant, ageLimite as libelle, descPegi as description
                        FROM pegi
                        ORDER BY ageLimite';
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
    // Pour la liste déroulante dans v_lesJeux.php
    public function getLesPegiListe(): array {
        $requete =  'SELECT idPegi, ageLimite
                      FROM pegi
                      ORDER BY ageLimite';
        try	{	 
            $resultat = PdoAgora::$monPdo->query($requete);
            $tbPegiListe  = $resultat->fetchAll(PDO::FETCH_OBJ);	
            return $tbPegiListe;		
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
                    WHERE loginMembre = :leLoginMembre'); //AND mdpMembre = :leMdpMembre');
            $requete_prepare->bindValue(':leLoginMembre', $loginMembre, PDO::PARAM_STR);
            //$requete_prepare->bindValue(':leMdpMembre', $mdpMembre, PDO::PARAM_STR);
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