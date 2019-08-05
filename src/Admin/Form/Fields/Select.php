<?php

namespace Arbory\Base\Admin\Form\Fields;

use Arbory\Base\Admin\Form\Fields\Concerns\HasSelectOptions;
use Arbory\Base\Admin\Form\Fields\Renderer\SelectFieldRenderer;
use Illuminate\Http\Request;

/**
 * Class Dropdown
 * @package Arbory\Base\Admin\Form\Fields
 */
class Select extends ControlField
{
    use HasSelectOptions;

    protected $control = \Arbory\Base\Admin\Form\Controls\SelectControl::class;

    protected $rendererClass = SelectFieldRenderer::class;

    /**
     * @var bool
     */
    protected $multiple = false;

    /**
     * @param Request $request
     * @throws \RuntimeException
     */
    public function beforeModelSave( Request $request )
    {
        $property = $this->getName();
        $value = $request->has( $this->getNameSpacedName() )
            ? $request->input( $this->getNameSpacedName() )
            : null;

        if( !$this->containsValidValues( $value ) )
        {
            throw new \RuntimeException( sprintf( 'Bad select field value for "%s"', $this->getName() ) );
        }

        if( is_array( $value ) )
        {
            $value = implode( ',', $value );
        }

        $this->getModel()->setAttribute( $property, $value );
    }

    /**
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @param bool $multiple
     * @return self
     */
    public function setMultiple( bool $multiple )
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * @param mixed $input
     * @return bool
     */
    public function containsValidValues( $input ): bool
    {
        if( !is_array( $input ) )
        {
            $input = [ $input ];
        }

        foreach( $input as $item )
        {
            if (!empty($item) && !$this->getOptions()->has($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array|mixed
     */
    public function getValue()
    {
        $value = parent::getValue();

        return $this->isMultiple() ? explode( ',', $value ) : $value;
    }
}
