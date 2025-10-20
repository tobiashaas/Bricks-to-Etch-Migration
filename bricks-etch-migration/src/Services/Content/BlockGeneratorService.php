<?php
/**
 * Block Generator Service
 * 
 * Generates Gutenberg blocks from Bricks elements
 */

namespace BricksEtchMigration\Services\Content;

use BricksEtchMigration\Services\CSS\StyleMapService;

class BlockGeneratorService {
    public function __construct(
        private StyleMapService $styleMapService
    ) {}
    
    /**
     * Generate Gutenberg block from Bricks element
     * 
     * @param array $element Bricks element
     * @return string Gutenberg block HTML
     */
    public function generate(array $element): string {
        $type = $element['name'] ?? 'div';
        
        return match($type) {
            'section' => $this->generateSection($element),
            'container' => $this->generateContainer($element),
            'div', 'block' => $this->generateDiv($element),
            'text-basic' => $this->generateParagraph($element),
            'heading' => $this->generateHeading($element),
            'image' => $this->generateImage($element),
            'button' => $this->generateButton($element),
            default => $this->generateGroup($element)
        };
    }
    
    /**
     * Generate section block
     */
    private function generateSection(array $element): string {
        return $this->generateEtchBlock($element, 'section');
    }
    
    /**
     * Generate container block
     */
    private function generateContainer(array $element): string {
        return $this->generateEtchBlock($element, 'container');
    }
    
    /**
     * Generate div block
     */
    private function generateDiv(array $element): string {
        return $this->generateEtchBlock($element, 'flex-div');
    }
    
    /**
     * Generate Etch block with metadata
     */
    private function generateEtchBlock(array $element, string $etchType): string {
        $styleIds = $this->getStyleIds($element);
        $classes = $this->getClasses($element);
        $content = $element['content'] ?? '';
        $label = $element['label'] ?? ucfirst($etchType);
        
        $metadata = [
            'name' => $label,
            'etchData' => [
                'origin' => 'etch',
                'name' => $label,
                'styles' => $styleIds,
                'attributes' => [
                    'data-etch-element' => $etchType,
                    'class' => implode(' ', $classes)
                ],
                'block' => [
                    'type' => 'html',
                    'tag' => $this->getTagForType($etchType)
                ]
            ]
        ];
        
        $classAttr = !empty($classes) ? ' class="' . esc_attr(implode(' ', $classes)) . '"' : '';
        $tag = $this->getTagForType($etchType);
        
        $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
        $className = implode(' ', $classes);
        
        $block = "<!-- wp:group {\"metadata\":{$metadataJson},\"className\":\"{$className}\",\"tagName\":\"{$tag}\"} -->\n";
        $block .= "<div class=\"wp-block-group{$classAttr}\">\n";
        $block .= $content;
        $block .= "</div>\n";
        $block .= "<!-- /wp:group -->";
        
        return $block;
    }
    
    /**
     * Generate paragraph block
     */
    private function generateParagraph(array $element): string {
        $content = $element['content'] ?? '';
        return "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->";
    }
    
    /**
     * Generate heading block
     */
    private function generateHeading(array $element): string {
        $content = $element['content'] ?? '';
        $level = $element['settings']['tag'] ?? 'h2';
        $levelNum = (int)str_replace('h', '', $level);
        
        return "<!-- wp:heading {\"level\":{$levelNum}} -->\n<{$level}>{$content}</{$level}>\n<!-- /wp:heading -->";
    }
    
    /**
     * Generate image block
     */
    private function generateImage(array $element): string {
        $imageId = $element['settings']['image']['id'] ?? 0;
        $url = $element['settings']['image']['url'] ?? '';
        
        return "<!-- wp:image {\"id\":{$imageId}} -->\n<figure class=\"wp-block-image\"><img src=\"{$url}\" alt=\"\" /></figure>\n<!-- /wp:image -->";
    }
    
    /**
     * Generate button block
     */
    private function generateButton(array $element): string {
        $text = $element['settings']['text'] ?? 'Button';
        $link = $element['settings']['link']['url'] ?? '#';
        
        return "<!-- wp:button -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link\" href=\"{$link}\">{$text}</a></div>\n<!-- /wp:button -->";
    }
    
    /**
     * Generate generic group block
     */
    private function generateGroup(array $element): string {
        return $this->generateEtchBlock($element, 'container');
    }
    
    /**
     * Get style IDs for element
     */
    private function getStyleIds(array $element): array {
        $styleIds = [];
        
        // Add element style
        if (isset($element['etch_type'])) {
            $styleIds[] = 'etch-' . $element['etch_type'] . '-style';
        }
        
        // Get from _cssGlobalClasses
        if (isset($element['settings']['_cssGlobalClasses']) && is_array($element['settings']['_cssGlobalClasses'])) {
            $bricksIds = $element['settings']['_cssGlobalClasses'];
            $resolvedIds = $this->styleMapService->resolveStyleIds($bricksIds);
            $styleIds = array_merge($styleIds, $resolvedIds);
        }
        
        return $styleIds;
    }
    
    /**
     * Get CSS classes for element
     */
    private function getClasses(array $element): array {
        $classes = ['wp-block-group'];
        
        // Get from _cssClasses
        if (isset($element['settings']['_cssClasses']) && !empty($element['settings']['_cssClasses'])) {
            $cssClasses = is_string($element['settings']['_cssClasses'])
                ? explode(' ', $element['settings']['_cssClasses'])
                : $element['settings']['_cssClasses'];
            
            $classes = array_merge($classes, $cssClasses);
        }
        
        return $classes;
    }
    
    /**
     * Get HTML tag for Etch type
     */
    private function getTagForType(string $type): string {
        return match($type) {
            'section' => 'section',
            'container' => 'div',
            'flex-div' => 'div',
            default => 'div'
        };
    }
}
