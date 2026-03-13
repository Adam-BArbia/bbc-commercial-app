<?php

namespace App\Form;

use App\Entity\PdfTheme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PdfThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $anchors = $options['anchor_values'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du thème',
            ])
            ->add('documentType', ChoiceType::class, [
                'label' => 'Type de document',
                'choices' => [
                    'Bon de Livraison' => PdfTheme::TYPE_DELIVERY,
                    'Facture'         => PdfTheme::TYPE_INVOICE,
                    'Les deux'        => PdfTheme::TYPE_BOTH,
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du thème (A4 portrait)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '8M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Utilisez une image JPG ou PNG.',
                    ]),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Thème actif pour ce type',
                'required' => false,
            ]);

        $this->addAnchorField($builder, 'header_x', 'X en-tête (mm)', $anchors);
        $this->addAnchorField($builder, 'header_y', 'Y en-tête (mm)', $anchors);
        $this->addAnchorField($builder, 'header_w', 'Largeur en-tête (mm)', $anchors, 180.0, 40, 210);
        $this->addAnchorField($builder, 'header_h', 'Hauteur en-tête (mm)', $anchors, 24.0, 10, 120);
        $this->addAnchorField($builder, 'title_x', 'X titre (mm)', $anchors, 15.0, 0, 210);
        $this->addAnchorField($builder, 'title_y', 'Y titre (mm)', $anchors, 10.0, 0, 297);
        $this->addAnchorField($builder, 'title_w', 'Largeur titre (mm)', $anchors, 180.0, 20, 210);
        $this->addAnchorField($builder, 'title_h', 'Hauteur titre (mm)', $anchors, 10.0, 5, 120);
        $this->addAnchorField($builder, 'client_x', 'X bloc client (mm)', $anchors);
        $this->addAnchorField($builder, 'client_y', 'Y bloc client (mm)', $anchors);
        $this->addAnchorField($builder, 'client_w', 'Largeur bloc client (mm)', $anchors, 180.0, 40, 210);
        $this->addAnchorField($builder, 'client_h', 'Hauteur bloc client (mm)', $anchors, 36.0, 10, 160);
        $this->addAnchorField($builder, 'table_x', 'X tableau lignes (mm)', $anchors);
        $this->addAnchorField($builder, 'table_y', 'Y tableau lignes (mm)', $anchors);
        $this->addAnchorField($builder, 'table_w', 'Largeur tableau lignes (mm)', $anchors, 180.0, 40, 210);
        $this->addAnchorField($builder, 'table_h', 'Hauteur tableau lignes (mm)', $anchors, 120.0, 20, 220);
        $this->addAnchorField($builder, 'totals_x', 'X bloc totaux (mm)', $anchors);
        $this->addAnchorField($builder, 'totals_y', 'Y bloc totaux (mm)', $anchors);
        $this->addAnchorField($builder, 'totals_w', 'Largeur bloc totaux (mm)', $anchors, 70.0, 30, 210);
        $this->addAnchorField($builder, 'totals_h', 'Hauteur bloc totaux (mm)', $anchors, 46.0, 10, 140);
        $this->addAnchorField($builder, 'footer_x', 'X pied de page (mm)', $anchors);
        $this->addAnchorField($builder, 'footer_y', 'Y pied de page (mm)', $anchors);
        $this->addAnchorField($builder, 'footer_w', 'Largeur pied de page (mm)', $anchors, 180.0, 40, 210);
        $this->addAnchorField($builder, 'footer_h', 'Hauteur pied de page (mm)', $anchors, 18.0, 8, 120);
        $this->addAnchorField($builder, 'signature_x', 'X signature/cachet (mm)', $anchors, 145.0, 0, 210);
        $this->addAnchorField($builder, 'signature_y', 'Y signature/cachet (mm)', $anchors, 282.0, 0, 297);
        $this->addAnchorField($builder, 'signature_w', 'Largeur signature/cachet (mm)', $anchors, 50.0, 20, 210);
        $this->addAnchorField($builder, 'signature_h', 'Hauteur signature/cachet (mm)', $anchors, 10.0, 5, 120);
        $this->addAnchorField($builder, 'header_title_font_size', 'Taille titre en-tête', $anchors, 14.0, 8, 40);
        $this->addAnchorField($builder, 'font_size', 'Taille police', $anchors, 9.0, 6, 16);
        $this->addAnchorField($builder, 'line_height', 'Hauteur ligne', $anchors, 5.0, 3, 12);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PdfTheme::class,
            'anchor_values' => [],
        ]);
    }

    /**
     * @param array<string, mixed> $anchors
     */
    private function addAnchorField(
        FormBuilderInterface $builder,
        string $name,
        string $label,
        array $anchors,
        float $default = 10.0,
        int $min = 0,
        int $max = 300
    ): void {
        $builder->add($name, NumberType::class, [
            'mapped' => false,
            'required' => true,
            'label' => $label,
            'scale' => 2,
            'data' => isset($anchors[$name]) ? (float) $anchors[$name] : $default,
            'attr' => [
                'step' => '0.1',
                'min' => $min,
                'max' => $max,
            ],
        ]);
    }
}
