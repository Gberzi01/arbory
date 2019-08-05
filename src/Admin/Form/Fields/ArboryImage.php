<?php declare( strict_types=1 );

namespace Arbory\Base\Admin\Form\Fields;

use Arbory\Base\Admin\Form\Fields\Renderer\ImageFieldRenderer;

/**
 * Class ArboryImage
 * @package Arbory\Base\Admin\Form\Fields
 */
final class ArboryImage extends ArboryFile
{
    protected $rendererClass = ImageFieldRenderer::class;
}
