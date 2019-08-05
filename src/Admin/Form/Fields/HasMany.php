<?php

namespace Arbory\Base\Admin\Form\Fields;

use Closure;
use Arbory\Base\Admin\Form\Fields\Concerns\HasRelationships;
use Arbory\Base\Admin\Form\FieldSet;
use Arbory\Base\Admin\Form\Fields\Renderer\NestedFieldRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;

/**
 * Class HasMany
 * @package Arbory\Base\Admin\Form\Fields
 */
class HasMany extends AbstractRelationField implements NestedFieldInterface
{
    use HasRelationships;

    /**
     * @var Closure
     */
    protected $fieldSetCallback;

    /**
     * @var string
     */
    protected $orderBy;

    protected $rendererClass = NestedFieldRenderer::class;

    protected $style = 'nested';

    protected $isSortable = false;

    /**
     * AbstractRelationField constructor.
     * @param string $name
     * @param Closure $fieldSetCallback
     */
    public function __construct( $name, Closure $fieldSetCallback )
    {
        parent::__construct( $name );

        $this->fieldSetCallback = $fieldSetCallback;
    }

    /**
     * @return bool
     */
    public function canAddRelationItem()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canSortRelationItems()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function canRemoveRelationItems()
    {
        return true;
    }

    /**
     * @param $index
     * @param Model $model
     * @return FieldSet
     */
    public function getRelationFieldSet( $model, $index )
    {
        $fieldSet = new FieldSet( $model, $this->getNameSpacedName() . '.' . $index );
        $fieldSetCallback = $this->fieldSetCallback;
        $fieldSetCallback( $fieldSet );

        $fieldSet->prepend(
            ( new Hidden( $model->getKeyName() ) )
                ->setValue( $model->getKey() )
        );

        if( $this->isSortable() && $this->getOrderBy() )
        {
            $fieldSet->prepend(
                ( new Hidden( $this->getOrderBy() ) )
                    ->setValue( $model->{$this->getOrderBy()} )
            );
        }

        return $fieldSet;
    }

    /**
     * @param Request $request
     */
    public function beforeModelSave( Request $request )
    {

    }

    /**
     * @param Request $request
     */
    public function afterModelSave(Request $request)
    {
        $items = (array)$request->input($this->getNameSpacedName(), []);

        foreach ($items as $index => $item) {
            $relatedModel = $this->findRelatedModel($item);

            if (filter_var(array_get($item, '_destroy'), FILTER_VALIDATE_BOOLEAN)) {
                $relatedModel->delete();

                continue;
            }

            $relation = $this->getRelation();

            if ($relation instanceof MorphMany) {
                $relatedModel->fill(array_only($item, $relatedModel->getFillable()));
                $relatedModel->setAttribute($relation->getMorphType(), $relation->getMorphClass());
            }

            if(! $relation instanceof BelongsToMany) {
                $relatedModel->setAttribute($relation->getForeignKeyName(), $this->getModel()->getKey());
            }

            $relatedFieldSet = $this->getRelationFieldSet(
                $relatedModel,
                $index
            );

            foreach ($relatedFieldSet->getFields() as $field) {
                $field->beforeModelSave($request);
            }

            if($relation instanceof BelongsToMany) {
                $relation->save($relatedModel);
            } else {
                $relatedModel->save();
            }

            foreach ($relatedFieldSet->getFields() as $field) {
                $field->afterModelSave($request);
            }
        }
    }


    /**
     * @param $variables
     * @return Model
     */
    private function findRelatedModel( $variables )
    {
        $relation = $this->getRelation();

        $relatedModelId = array_get( $variables, $relation->getRelated()->getKeyName() );

        return $relation->getRelated()->findOrNew( $relatedModelId );
    }

    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    /**
     * @return string|null
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param string $orderBy
     * @return $this
     */
    public function setOrderBy( string $orderBy )
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        $rules = [];

        foreach( $this->getRelationFieldSet( $this->getRelatedModel(), '*' )->getFields() as $field )
        {
            $rules = array_merge( $rules, $field->getRules() );
        }

        return $rules;
    }

    public function getNestedFieldSet( $model )
    {
        return $this->getRelationFieldSet( $model, 0 );
    }

    /**
     * Make this field sortable
     *
     * @param string $field
     *
     * @return $this
     */
    public function sortable( $field = 'position' )
    {
        $this->isSortable = true;
        $this->setOrderBy($field);

        return $this;
    }
}
