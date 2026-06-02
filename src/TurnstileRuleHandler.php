<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3Turnstile;

use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

/**
 * @api
 */
final readonly class TurnstileRuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private ?TurnstileClient $client = null,
        private ?RequestProviderInterface $requestProvider = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-turnstile',
    ) {}

    private function client(): TurnstileClient
    {
        return $this->client
            ?? TurnstileRegistry::client()
            ?? throw new \RuntimeException('TurnstileClient is not available. Ensure rasuvaeff/yii3-turnstile bootstrap is registered.');
    }

    #[\Override]
    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof TurnstileRule) {
            throw new UnexpectedRuleException(TurnstileRule::class, $rule);
        }

        $result = new Result();

        if (!\is_string($value) || $value === '') {
            return $result->addError(
                $this->translate($rule->getMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                ],
            );
        }

        $clientIp = $rule->getSendRemoteIp()
            ? $this->resolveClientIp()
            : null;

        $secret = $rule->getSecret();
        $verificationResult = $secret !== null
            ? $this->client()->verifyWithSecret(token: $value, secret: $secret, clientIp: $clientIp)
            : $this->client()->verify(token: $value, clientIp: $clientIp);

        if (!$verificationResult->success) {
            return $result->addError(
                $this->translate($rule->getMessage()),
                [
                    'property' => $context->getTranslatedProperty(),
                    'errorCodes' => implode(', ', $verificationResult->errorCodes),
                ],
            );
        }

        return $result;
    }

    private function translate(string $message): string
    {
        $translator = $this->translator ?? TurnstileRegistry::translator();
        if ($translator === null) {
            return $message;
        }

        return $translator->translate(
            $message,
            [],
            $this->translationCategory,
        );
    }

    private function resolveClientIp(): ?string
    {
        $provider = $this->requestProvider ?? TurnstileRegistry::requestProvider();
        if ($provider === null) {
            return null;
        }

        try {
            $serverParams = $provider->get()->getServerParams();
        } catch (RequestNotSetException) {
            return null;
        }

        if (!isset($serverParams['REMOTE_ADDR']) || !\is_string($serverParams['REMOTE_ADDR'])) {
            return null;
        }

        return $serverParams['REMOTE_ADDR'];
    }
}
