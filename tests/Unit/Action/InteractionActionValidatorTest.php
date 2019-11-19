<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit\Action;

use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Action\InteractionActionInterface;
use webignition\BasilModel\Identifier\DomIdentifier;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Action\InteractionActionValidator;
use webignition\BasilModelValidator\Identifier\IdentifierValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class InteractionActionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InteractionActionValidator
     */
    private $interactionActionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interactionActionValidator = InteractionActionValidator::create();
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(InteractionActionInterface $action, ResultInterface $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->interactionActionValidator->validate($action));
    }

    public function validateNotValidDataProvider(): array
    {
        $invalidIdentifier = new DomIdentifier('');

        $interactionActionWithInvalidIdentifier = new InteractionAction(
            '',
            ActionTypes::CLICK,
            $invalidIdentifier,
            ''
        );

        $attributeIdentifier = (new DomIdentifier(
            '.selector'
        ))->withAttributeName('attribute_name');

        $interactionActionWithAttributeIdentifier = new InteractionAction(
            '',
            ActionTypes::CLICK,
            $attributeIdentifier,
            ''
        );

        return [
            'interaction action with invalid identifier' => [
                'action' => $interactionActionWithInvalidIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithInvalidIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_INVALID_IDENTIFIER,
                    new InvalidResult(
                        $invalidIdentifier,
                        TypeInterface::IDENTIFIER,
                        IdentifierValidator::REASON_ELEMENT_LOCATOR_MISSING
                    )
                ),
            ],
            'interaction action with attribute identifier' => [
                'action' => $interactionActionWithAttributeIdentifier,
                'expectedResult' => new InvalidResult(
                    $interactionActionWithAttributeIdentifier,
                    TypeInterface::ACTION,
                    ActionValidator::REASON_UNACTIONABLE_IDENTIFIER
                ),
            ],
        ];
    }

    public function testValidateIsValid()
    {
        $action = ActionFactory::createFactory()->createFromActionString('click ".selector"');

        if ($action instanceof InteractionActionInterface) {
            $this->assertEquals(new ValidResult($action), $this->interactionActionValidator->validate($action));
        }
    }
}
