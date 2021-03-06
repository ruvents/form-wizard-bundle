<?php
declare(strict_types=1);

namespace Ruvents\FormWizardBundle;

use Ruvents\FormWizardBundle\Storage\StorageInterface;
use Ruvents\FormWizardBundle\Type\TypeFacadeFactory;
use Ruvents\FormWizardBundle\Type\WizardBuilder;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WizardFactory implements WizardFactoryInterface
{
    private $typeFacadeFactory;

    private $storage;

    public function __construct(TypeFacadeFactory $typeFacadeFactory, StorageInterface $storage)
    {
        $this->typeFacadeFactory = $typeFacadeFactory;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function createWizardBuilder(string $type, $data = null, array $options = []): WizardBuilder
    {
        $type = $this->typeFacadeFactory->createWizardTypeFacade($type);

        $resolver = new OptionsResolver();
        $this->configureWizardOptions($resolver);
        $type->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $builder = new WizardBuilder($this->storage, $this->typeFacadeFactory, $type, $options);
        $builder->setData($data);
        $type->build($builder);

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function createWizard(string $type, $data = null, array $options = []): Wizard
    {
        return $this->createWizardBuilder($type, $data, $options)->getWizard();
    }

    private function configureWizardOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'storage_key',
            ])
            ->setDefaults([
                'data_class' => null,
                'empty_data' => function (Options $options) {
                    $class = $options['data_class'];

                    if ($class) {
                        return new $class;
                    }

                    return null;
                },
            ])
            ->setAllowedTypes('storage_key', 'string')
            ->setAllowedTypes('data_class', ['null', 'string'])
            ->setAllowedTypes('empty_data', ['null', 'object']);
    }
}
