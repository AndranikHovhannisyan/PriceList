<?php

namespace AppBundle\Admin;

use AppBundle\Entity\Product;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProductAdmin extends AbstractAdmin
{
    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            ->add('code')
            ->add('price')
            ->add('regionPrice')
            ->add('type', null, [], ChoiceType::class, array(
                'choices' => array(
                    Product::$Types[Product::ECONOMIC] => Product::ECONOMIC,
                    Product::$Types[Product::JUICE]    => Product::JUICE
                )
            ))
            ->add('enabled')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('enabled', null, ['editable' => true])
            ->add('name')
            ->add('code')
            ->add('price')
            ->add('regionPrice')
            ->add('typeName')
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('code')
            ->add('price')
            ->add('regionPrice')
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    Product::$Types[Product::ECONOMIC] => Product::ECONOMIC,
                    Product::$Types[Product::JUICE]    => Product::JUICE
                )
            ))
            ->add('enabled')
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
            ->add('code')
            ->add('price')
            ->add('regionPrice')
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    Product::$Types[Product::ECONOMIC] => Product::ECONOMIC,
                    Product::$Types[Product::JUICE]    => Product::JUICE
                )
            ))
            ->add('enabled')
        ;
    }
}
