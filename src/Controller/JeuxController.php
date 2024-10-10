<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

require_once __DIR__ . '/modele/class.PdoAgora.inc.php';

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use PdoAgora;

class JeuxController extends AbstractController
{
    /**
     * fonction pour afficher la liste des jeux
     * @param $db
     * @param $idGenreModif positionné si demande de modification
     * @param $idGenreNotif positionné si mise à jour dans la vue
     * @param $notification pour notifier la mise à jour dans la vue
     */
    private function afficherJeu(PdoAgora $db, string $idJeuModif, string $idJeuNotif, string $notification) {
        $tbMembres = $db->getLesMembres(); 
        $tbJeux = $db->getLesJeux();
        $tbPlateformes = $db->getLesPlateformes();
        $tbPegis = $db->getLesPegis();
        $tbGenres = $db->getLesGenres();
        $tbMarques = $db->getLesMarques();

        return $this->render('lesJeux.html.twig', array(
            'menuActif' => 'Jeux',
            'tbJeux' => $tbJeux,
            'tbPlateformes' => $tbPlateformes,
            'tbPegis' => $tbPegis,
            'tbGenres' => $tbGenres,
            'tbMarques' => $tbMarques,
            'idJeuModif' => $idJeuModif,
            'idJeuNotif' => $idJeuNotif,
            'notification' => $notification
        ));
    }
    #[Route('/jeux', name: 'jeux_afficher')]
    public function index(SessionInterface $session)
    {
        if ($session->has('idUtilisateur')) {
            $db = PdoAgora::getPdoAgora();
            return $this->afficherJeu($db, -1, -1, 'rien');
        } else {
            return $this->render('connexion.html.twig');
        }
    }
    #[Route('/jeux/ajouter', name: 'jeux_ajouter')]
    public function ajouter(SessionInterface $session, Request $request)
    {
        $db = PdoAgora::getPdoAgora();
        if (!empty($request->request->get('txtRefJeu'))) {
            $idJeuNotif = $db->ajouterJeu($request->request->get('txtRefJeu'), 
                $request->request->get('txtIdPlateformeJeu'), 
                $request->request->get('txtIdPegiJeu'), 
                $request->request->get('txtIdGenreJeu'), 
                $request->request->get('txtIdMarqueJeu'), 
                $request->request->get('txtNomJeu'), 
                $request->request->get('prixJeu'), 
                $request->request->get('txtDateParutionJeu'));
            $notification = 'Ajouté';
        }

        return $this->afficherJeu($db, -1, $idJeuNotif, $notification);
    }
    #[Route('/jeux/demanderModifier', name: 'jeux_demanderModifier')]
    public function demanderModifier(SessionInterface $session, Request $request)
    {
        $db = PdoAgora::getPdoAgora();
        return $this->afficherJeu(
            $db,
            $request->request->get('txtRefJeu'),
            -1,
            'rien'
        );
    }
    #[Route('/jeux/validerModifier', name: 'jeux_validerModifier')]
    public function validerModifier(SessionInterface $session, Request $request)
    {
        $db = PdoAgora::getPdoAgora();
        $db->modifierJeu($request->request->get('txtRefJeu'), 
            $request->request->get('txtIdPlateformeJeu'), 
            $request->request->get('txtIdPegiJeu'), 
            $request->request->get('txtIdGenreJeu'), 
            $request->request->get('txtIdMarqueJeu'), 
            $request->request->get('txtNomJeu'), 
            $request->request->get('prixJeu'), 
            $request->request->get('txtDateParutionJeu'));
        return $this->afficherJeu(
            $db,
            -1,
            $request->request->get('txtRefJeu'),
            'Modifié'
        );
    }
    #[Route('/jeux/supprimer', name: 'jeux_supprimer')]
    public function supprimer(SessionInterface $session, Request $request)
    {
        $db = PdoAgora::getPdoAgora();
        $db->supprimerJeu($request->request->get('txtRefJeu'));
        $this->addFlash(
            'success',
            'Le jeu a été supprimé'
        );
        return $this->afficherJeu($db, -1, -1, 'rien');
    }
}
