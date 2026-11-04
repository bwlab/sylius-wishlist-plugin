<?php

declare(strict_types=1);

namespace Bwlab\SyliusWishlistPlugin\Form\Type;

use Bwlab\SyliusWishlistPlugin\Entity\WishlistChannelSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

final class ChannelSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('guestLifetimeDays', IntegerType::class, [
            'required' => false,
            'label' => 'bwlab_wishlist.ui.admin.settings.guest_lifetime_days',
            'help' => 'bwlab_wishlist.ui.admin.settings.guest_lifetime_days_help',
            'help_translation_parameters' => ['%days%' => $options['default_days']],
            'attr' => ['min' => 1, 'placeholder' => (string) $options['default_days']],
            'constraints' => [new GreaterThanOrEqual(['value' => 1, 'message' => 'bwlab_wishlist.admin.settings.min_one_day'])],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults(['data_class' => WishlistChannelSettings::class, 'translation_domain' => 'messages'])
            ->setRequired('default_days')
            ->setAllowedTypes('default_days', 'int')
        ;
    }
}
