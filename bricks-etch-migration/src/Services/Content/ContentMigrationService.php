<?php
/**
 * Content Migration Service
 * 
 * Orchestrates content migration from Bricks to Etch
 */

namespace BricksEtchMigration\Services\Content;

use BricksEtchMigration\Interfaces\ServiceInterface;
use BricksEtchMigration\DTOs\MigrationResultDTO;

class ContentMigrationService implements ServiceInterface {
    public function __construct(
        private BlockGeneratorService $blockGenerator
    ) {}
    
    /**
     * Execute content migration
     * 
     * @param array $params ['post_id' => int]
     * @return MigrationResultDTO
     */
    public function execute(array $params): mixed {
        $postId = $params['post_id'] ?? 0;
        
        if (!$postId) {
            return MigrationResultDTO::failure(0, 1, ['No post ID provided']);
        }
        
        // Get Bricks content
        $bricksContent = get_post_meta($postId, '_bricks_page_content_2', true);
        
        if (empty($bricksContent)) {
            return MigrationResultDTO::failure(0, 1, ['No Bricks content found']);
        }
        
        // Convert to Gutenberg blocks
        $gutenbergContent = $this->convertToGutenberg($bricksContent);
        
        // Update post
        $result = wp_update_post([
            'ID' => $postId,
            'post_content' => $gutenbergContent
        ]);
        
        if (is_wp_error($result)) {
            return MigrationResultDTO::failure(1, 1, [$result->get_error_message()]);
        }
        
        return MigrationResultDTO::success(1, ['post_id' => $postId]);
    }
    
    /**
     * Convert Bricks content to Gutenberg
     * 
     * @param array $bricksElements
     * @return string Gutenberg HTML
     */
    private function convertToGutenberg(array $bricksElements): string {
        $blocks = [];
        
        foreach ($bricksElements as $element) {
            $block = $this->blockGenerator->generate($element);
            $blocks[] = $block;
        }
        
        return implode("\n\n", $blocks);
    }
}
