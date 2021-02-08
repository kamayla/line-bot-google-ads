<?php

namespace App\Service\Line\Message;

use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\Constant\Flex\ComponentLayout;
use LINE\LINEBot\Constant\Flex\ComponentAlign;
use LINE\LINEBot\Constant\Flex\ComponentFontWeight;
use LINE\LINEBot\Constant\Flex\ComponentSpacing;
use LINE\LINEBot\Constant\Flex\ComponentButtonStyle;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ButtonComponentBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;

class YesNo
{
    /**
     * 質問文
     *
     * @var string
     */
    private $questionText;

    /**
     * 回答
     *
     * @var array
     */
    private $answerArray = [];

    public function __construct(string $questionText, array $answerArray)
    {
        $this->questionText = $questionText;
        foreach ($answerArray as $answer) {
            $this->answerArray[] = ButtonComponentBuilder::builder()
                                    ->setStyle(ComponentButtonStyle::PRIMARY)
                                    ->setAction(
                                        new PostbackTemplateActionBuilder($answer['label'], $answer['answer'])
                                    );
        }
    }

    public function execute()
    {
        return FlexMessageBuilder::builder()
                ->setAltText($this->questionText)
                ->setContents(
                    BubbleContainerBuilder::builder()
                    ->setHeader(
                        BoxComponentBuilder::builder()
                        ->setLayout(ComponentLayout::VERTICAL)
                        ->setContents([
                            TextComponentBuilder::builder()
                                ->setText($this->questionText)
                                ->setAlign(ComponentAlign::CENTER)
                                ->setWrap(true)
                                ->setWeight(ComponentFontWeight::BOLD)
                                ->setColor('#17c950'),
                        ])
                    )
                    ->setFooter(
                        BoxComponentBuilder::builder()
                        ->setLayout(ComponentLayout::VERTICAL)
                        ->setSpacing(ComponentSpacing::SM)
                        ->setContents($this->answerArray)
                    )
                );
    }
}
