<?php

namespace App\Extensions\Chatbot\System\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishTriggerAssetsCommand extends Command
{
    protected $signature = 'chatbot:publish-trigger-assets';
    protected $description = 'Publish chatbot trigger assets to public directory';

    public function handle()
    {
        $this->info('Publishing chatbot trigger assets...');

        // Define source and destination paths
        $sourcePaths = [
            'js' => __DIR__ . '/../../../resources/js',
            'css' => __DIR__ . '/../../../resources/css',
        ];

        $destinationPaths = [
            'js' => public_path('vendor/chatbot/js'),
            'css' => public_path('vendor/chatbot/css'),
        ];

        // Ensure destination directories exist
        foreach ($destinationPaths as $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->info("Created directory: {$path}");
            }
        }

        // Copy JavaScript files
        if (File::exists($sourcePaths['js'])) {
            $jsFiles = File::files($sourcePaths['js']);
            foreach ($jsFiles as $file) {
                $destination = $destinationPaths['js'] . '/' . $file->getFilename();
                File::copy($file->getPathname(), $destination);
                $this->info("Published: {$file->getFilename()}");
            }
        }

        // Copy CSS files
        if (File::exists($sourcePaths['css'])) {
            $cssFiles = File::files($sourcePaths['css']);
            foreach ($cssFiles as $file) {
                $destination = $destinationPaths['css'] . '/' . $file->getFilename();
                File::copy($file->getPathname(), $destination);
                $this->info("Published: {$file->getFilename()}");
            }
        }

        $this->info('✅ Chatbot trigger assets published successfully!');
        
        $this->newLine();
        $this->info('📁 Published files:');
        $this->info('   - JavaScript files: ' . public_path('vendor/chatbot/js/'));
        $this->info('   - CSS files: ' . public_path('vendor/chatbot/css/'));
        
        $this->newLine();
        $this->info('🚀 Next steps:');
        $this->info('   1. Run migrations: php artisan migrate');
        $this->info('   2. Configure triggers in chatbot wizard');
        $this->info('   3. Test triggers on your website');

        return 0;
    }
}