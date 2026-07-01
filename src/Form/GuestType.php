<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'    => 'Nom',
            'required' => true,
        ]);
        $builder->add('email', EmailType::class, [
            'label'    => 'Email',
            'required' => true,
        ]);
        $builder->add('admin', ChoiceType::class, [
            'label'   => 'Administrateur',
            'choices' => [
                'Oui' => true,
                'Non' => false,
            ],
        ]);
        $builder->add('description', TextareaType::class, [
            'label'    => 'Description',
            'required' => false,
        ]);
        $builder->add('plainPassword', PasswordType::class, [
            'label'    => 'Mot de passe',
            'required' => true,
            'mapped'   => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => User::class,
            'csrf_protection' => true,
            'csrf_token_id'   => 'add-guest',
        ]);
    }
}
