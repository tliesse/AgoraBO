<?php
    // src/Controller/GenresController.php
    namespace App\Controller;
    use Symfony\Component\HttpFoundation\Response;

    require_once __DIR__ . '/modele/class.PdoAgora.inc.php';

    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Session\SessionInterface;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
    use PdoAgora;

    class GenresController extends AbstractController {
        /**
         * fonction pour afficher la liste des genres
         * @param $db
         * @param $idGenreModif positionné si demande de modification
         * @param $idGenreNotif positionné si mise à jour dans la vue
         * @param $notification pour notifier la mise à jour dans la vue
        */

        //! afficherGENRE
        private function afficherGenre(PdoAgora $db, int $idGenreModif, int $idGenreNotif, string $notification ) {
            $tbMembres = $db->getLesMembres();
            $tbGenres = $db->getLesGenres();
            return $this->render('lesGenres.html.twig', array(
                'menuActif' => 'Jeux',
                'tbGenres' => $tbGenres,
                'tbMembres' => $tbMembres,
                'idGenreModif' => $idGenreModif,
                'idGenreNotif' => $idGenreNotif,
                'notification' => $notification));
        }
        #[Route('/genres', name: 'genres_afficher')]
        //! index
        public function index(SessionInterface $session) {
            if ($session->has('idUtilisateur')) {
                $db = PdoAgora::getPdoAgora();
                return $this->afficherGenre($db, -1, -1, 'rien');
            } else {
                return $this->render('connexion.html.twig');
            }
        }

        #[Route('/genres/ajouter', name: 'genres_ajouter')]
        //! ajouter
        public function ajouter(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            if (!empty($request->request->get('txtLibGenre'))) {
                $idGenreNotif = $db->ajouterGenre($request->request->get('txtLibGenre'),
                $request->request->get('lstMembre'));
                $notification = 'Ajouté';
            }
            return $this->afficherGenre($db, -1, $idGenreNotif, $notification);
        }

        #[Route('/genres/demanderModifier', name: 'genres_demanderModifier')]
        //! demanderModifier
        public function demanderModifier(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            return $this->afficherGenre($db, $request->request->get('txtIdGenre'), -1, 'rien');
        }

        #[Route('/genres/validerModifier', name: 'genres_validerModifier')]
        //! validerModifer
        public function validerModifier(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            $db->modifierGenre($request->request->get('txtIdGenre'), $request->request->get('txtLibGenre'), $request->request->get('lstMembre'));
            return $this->afficherGenre($db, -1, $request->request->get('txtIdGenre'), 'Modifié');
        }

        #[Route('/genres/supprimer', name: 'genres_supprimer')]
        //! supprimer
        public function supprimer(SessionInterface $session, Request $request) {
            $db = PdoAgora::getPdoAgora();
            $db->supprimerGenre($request->request->get('txtIdGenre'));
            $this->addFlash('success', 'Le genre a été supprimé');
            return $this->afficherGenre($db, -1, -1, 'rien');
        }
    }