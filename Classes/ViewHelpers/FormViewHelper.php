<?php

namespace Rovitch\HeadlessPagePassword\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Security\HashScope;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;

class FormViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper
{
    /**
     * @var array
     */
    protected $data = [];

    public function render(): string
    {
        $this->setFormActionUri();

        $this->addFieldNamePrefixToViewHelperVariableContainer();

        $this->data['elements'] = $this->renderChildren();

        $this->renderHiddenReferrerFields();
        $this->renderTrustedPropertiesField();
        $this->renderRequestTokenHiddenField();

        $this->removeFieldNamePrefixFromViewHelperVariableContainer();

        return json_encode($this->data);
    }

    /**
     * Sets the "action" attribute of the form tag
     */
    protected function setFormActionUri(): void
    {
        parent::setFormActionUri();
        $formActionUri = $this->tag->getAttribute('action');
        $this->data['action'] = html_entity_decode($formActionUri);
    }

    /**
     * Renders hidden form fields for referrer information about
     * the current controller and action.
     *
     * @return string Hidden fields with referrer information
     * @todo filter out referrer information that is equal to the target (e.g. same packageKey)
     */
    protected function renderHiddenReferrerFields(): string
    {
        /** @var RenderingContext $renderingContext */
        $renderingContext = $this->renderingContext;
        /** @var RequestInterface $request */
        $request = $renderingContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $controllerName = $request->getControllerName();
        $actionName = $request->getControllerActionName();
        $actionRequest = [
            '@extension' => $extensionName,
            '@controller' => $controllerName,
            '@action' => $actionName,
        ];
        $this->addHiddenField($this->prefixFieldName('__referrer[@extension]'), $extensionName);
        $this->addHiddenField($this->prefixFieldName('__referrer[@controller]'), $controllerName);
        $this->addHiddenField($this->prefixFieldName('__referrer[@action]'), $actionName);
        $this->addHiddenField(
            $this->prefixFieldName('__referrer[@request]'),
            $this->hashService->appendHmac(
                json_encode($actionRequest),
                class_exists(HashScope::class) ? HashScope::class::ReferringRequest->prefix() : ''
            )
        );
        $this->addHiddenField(
            $this->prefixFieldName('__referrer[arguments]'),
            $this->hashService->appendHmac(
                base64_encode(serialize($request->getArguments())),
                class_exists(HashScope::class) ? HashScope::class::ReferringArguments->prefix() : ''
            )
        );

        return '';
    }

    /**
     * Render the request hash field
     */
    protected function renderTrustedPropertiesField(): string
    {
        $formFieldNames = $this->renderingContext->getViewHelperVariableContainer()->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class, 'formFieldNames');
        $requestHash = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($formFieldNames, $this->getFieldNamePrefix());
        $this->addHiddenField($this->prefixFieldName('__trustedProperties'), $requestHash);

        return '';
    }

    protected function renderRequestTokenHiddenField(): string
    {
        $requestToken = $this->arguments['requestToken'] ?? null;
        $signingType = $this->arguments['signingType'] ?? null;

        $isTrulyRequestToken = is_int($requestToken) && $requestToken === 1
            || is_string($requestToken) && strtolower($requestToken) === 'true';
        $formAction = $this->tag->getAttribute('action');

        // basically "request token, yes" - uses form-action URI as scope
        if ($isTrulyRequestToken || $requestToken === '@nonce') {
            $requestToken = RequestToken::create($formAction);
        } elseif (is_string($requestToken) && $requestToken !== '') {
            // basically "request token with 'my-scope'" - uses 'my-scope'
            $requestToken = RequestToken::create($requestToken);
        }
        if (!$requestToken instanceof RequestToken) {
            return '';
        }
        if (strtolower((string)($this->arguments['method'] ?? '')) === 'get') {
            throw new \LogicException('Cannot apply request token for forms sent via HTTP GET', 1651775963);
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $securityAspect = SecurityAspect::provideIn($context);
        // @todo currently defaults to 'nonce', there might be a better strategy in the future
        $signingType = $signingType ?: 'nonce';
        $signingProvider = $securityAspect->getSigningSecretResolver()->findByType($signingType);
        if ($signingProvider === null) {
            throw new \LogicException(sprintf('Cannot find request token signing type "%s"', $signingType), 1664260307);
        }

        $signingSecret = $signingProvider->provideSigningSecret();
        $requestToken = $requestToken->withMergedParams(['request' => ['uri' => $formAction]]);

        $this->addHiddenField(RequestToken::PARAM_NAME, $requestToken->toHashSignedJwt($signingSecret));
        return '';
    }

    /**
     * @param $name
     * @param $value
     */
    protected function addHiddenField($name, $value)
    {
        $this->data['elements'][] = [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value
        ];
    }
}
