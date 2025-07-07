<?php
namespace Rovitch\HeadlessPagePassword\ViewHelpers\Form;

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;


class RegisterFieldViewHelper extends AbstractFormFieldViewHelper
{
    public function render(): string
    {
        $nameAttribute = $this->getName();
        $this->registerFieldNameForFormTokenGeneration($nameAttribute);
        return '';
    }
}
