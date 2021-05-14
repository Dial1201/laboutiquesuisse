<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;

class OrderCrudController extends AbstractCrudController
{

    private $em;
    private $crudUrlGenerator;

    public function __construct(EntityManagerInterface $em, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->em = $em;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }



    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $preparation = Action::new('preparation', 'Préparation en cours', 'fas fa-box-open')->linkToCrudAction('preparation');
        $delivery = Action::new('delivery', 'Livraison en cours', 'fas fa-truck')->linkToCrudAction('delivery');
        return $actions
            ->add('detail', $preparation)
            ->add('detail', $delivery)
            ->add('index', 'detail');
    }
    public function delivery(AdminContext $adminContext)
    {

        $order = $adminContext->getEntity()->getInstance();
        if ($order->getState() != 3) {

            $order->setState(3);
            $this->em->flush();
            $this->addFlash('notice', "<span style='color:orange;'><strong>La commande " . $order->getReference() . " est bien en cours de livraison </strong></span>");
        }


        $url = $this->crudUrlGenerator->build()
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function preparation(AdminContext $adminContext)
    {

        $order = $adminContext->getEntity()->getInstance();
        if ($order->getState() != 2) {

            $order->setState(2);
            $this->em->flush();
            $this->addFlash('notice', "<span style='color:green;'><strong>La commande " . $order->getReference() . " est bien en cours de préparation</strong></span>");
        }


        $url = $this->crudUrlGenerator->build()
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            DateField::new('createdAt', 'Commande le'),
            TextField::new('user.fullname', 'Utilisateur'),
            TextEditorField::new('delivery', 'Adresse de livraison')->onlyOnDetail(),
            MoneyField::new('total')->setCurrency('EUR'),
            TextField::new('carrierName', 'Transporteur'),
            MoneyField::new('carrierPrice', 'Frais de port')->setCurrency('EUR'),
            ChoiceField::new('state')->setChoices([
                'Non payée' => 0,
                'Payée' => 1,
                'Préparation en cours' => 2,
                'Livraison en cours' => 3
            ]),
            ArrayField::new('orderDetails', 'Produits achetés')->hideOnIndex()

        ];
    }
}
