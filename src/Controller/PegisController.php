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

    class PegisController extends AbstractController {
        /**
         * fonction pour afficher la liste des pegis
         * @param $db
         * @param $idPegiModif positionné si demande de modification
         * @param $idPegiNotif positionné si mise à jour dans la vue
         * @param $notification pour notifier la mise à jour dans la vue
        */

        //! afficherPEGI
        private function afficherPegi(PdoAgora $db, int $idPegiModif, int $idPegiNotif, string $notification ) {
            $tbMembres = $db->getLesMembres();
            $tbPegis = $db->getLesPegis();
            return $this->render('lesPegis.html.twig', array(
                'menuActif' => 'Jeux',
                'tbPegis' => $tbPegis, 
                'tbMembres' => $tbMembres,
                'idPegiModif' => $idPegiModif, 
                'idPegiNotif' => $idPegiNotif, 
                'notification' => $notification));
        }
        #[Route('/pegis', name: 'pegis_afficher')]

        //! index
        public function index(SessionInterface $session) {
            if ($session->has('idUtilisateur')) {
                $db = PdoAgora::getPdoAgora();
                return $this->afficherPegi($db, -1, -1, 'rien');
            } else {
                return $this->render('connexion.html.twig');
            }
        }   
        #[Route('/pegis/ajouter', name: 'pegis_ajouter')]
        
        //! ajouter
        public function ajouter(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            if (!empty($request->request->get('txtAgeLimite'))) { 
                $idPegiNotif = $db->ajouterPegi($request->request->get('txtAgeLimite'),
                    $request->request->get('txtDescPegi'),  
                $request->request->get('lstMembre'));
                $notification = 'Ajouté';
            }
            return $this->afficherPegi($db, -1, $idPegiNotif, $notification);
        }
        #[Route('/pegis/demanderModifier', name: 'pegis_demanderModifier')]
            
        //! demanderModifier
        public function demanderModifier(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            return $this->afficherPegi($db, $request->request->get('txtIdPegi'), -1, 'rien');
        }
        #[Route('/pegis/validerModifier', name: 'pegis_validerModifier')]
            
        //! validerModifer
        public function validerModifier(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            $db->modifierPegi($request->request->get('txtIdPegi'), 
                $request->request->get('txtAgeLimite'), 
                $request->request->get('txtDescPegi'), 
                $request->request->get('lstMembre')); 
            return $this->afficherPegi($db, -1, $request->request->get('txtIdPegi'), 'Modifié');
        }
        #[Route('/pegis/supprimer', name: 'pegis_supprimer')]

        //! supprimer
        public function supprimer(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();  
            $db->supprimerPegi($request->request->get('txtIdPegi'));
            $this->addFlash('success', 'Le pegi a été supprimé');
            return $this->afficherPegi($db, -1, -1, 'rien');
        }
    }