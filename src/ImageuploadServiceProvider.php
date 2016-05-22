<?php

namespace Matriphe\Imageupload;

use Illuminate\Support\ServiceProvider;

use ImageuploadTrait;

class ImageuploadServiceProvider extends ServiceProvider
{
    use ImageuploadTrait;
    
    protected $defer = false;

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'imageupload');

        $this->publishes([
            __DIR__.'/config/config.php' => config_path('imageupload.php'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
