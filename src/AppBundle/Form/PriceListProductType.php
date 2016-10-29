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

/**
 * Class PriceListProductType
 * @package AppBundle\Form\Type
 */
class PriceListProductType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', null, array('attr' => ['class' => 'form-control']))
            ->add('discount', null, array('attr' => ['class' => 'form-control']))
            ->add('quantity', null, array('attr' => ['class' => 'form-control']))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\PriceListProduct'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'app_bundle_price_list_product';
    }
}