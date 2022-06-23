<?php declare(strict_types=1);

namespace App\Form;

use App\DTO\EntryDto;
use App\Entity\Magazine;
use App\Form\Autocomplete\MagazineAutocompleteField;
use App\Form\DataTransformer\TagTransformer;
use App\Form\EventListener\DisableFieldsOnEntryEdit;
use App\Form\EventListener\ImageListener;
use App\Form\EventListener\RemoveFieldsOnEntryImageEdit;
use App\Form\Type\BadgesType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntryImageType extends AbstractType
{
    public function __construct(private ImageListener $imageListener)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextareaType::class)
            ->add('tags')
            ->add(
                'badges',
                BadgesType::class,
                [
                    'label' => 'Etykiety',
                ]
            )
            ->add(
                'magazine',
                EntityType::class,
                [
                    'class' => Magazine::class,
                    'choice_label' => 'name',
                ]
            )
            ->add('magazine', MagazineAutocompleteField::class)
            ->add('isAdult', CheckboxType::class)
            ->add('isEng', CheckboxType::class)
            ->add('isOc', CheckboxType::class)
            ->add('submit', SubmitType::class);

        $builder->get('tags')->addModelTransformer(
            new TagTransformer()
        );
        $builder->addEventSubscriber(new RemoveFieldsOnEntryImageEdit());
        $builder->addEventSubscriber(new DisableFieldsOnEntryEdit());
        $builder->addEventSubscriber($this->imageListener);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => EntryDto::class,
            ]
        );
    }
}
