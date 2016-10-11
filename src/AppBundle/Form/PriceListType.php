<?php
/**
 * Created by PhpStorm.
 * User: andranik
 * Date: 9/26/16
 * Time: 10:51 PM
 */
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\PriceListProductType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * Class PriceListType
 * @package AppBundle\Form\Type
 */
class PriceListType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('company')
            ->add('priceListProducts', CollectionType::class, array(
                'entry_type' => PriceListProductType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true
            ))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\PriceList'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'app_bundle_price_list';
    }
}