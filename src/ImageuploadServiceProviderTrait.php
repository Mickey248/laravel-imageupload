<?php

namespace Matriphe\Imageupload;

trait ImageuploadServiceProviderTrait
{
    public function register()
    {
        $this->app['imageupload'] = $this->app->share(function ($app) {
            $config = $this->config();

            return new Imagupload(
                $config['library'],
                $config['quality'],
                $config['uploadpath'],
                $config['newfilename'],
                $config['dimensions'],
                $config['suffix'],
                $config['exif']
            );
        });
    }
    
    protected function config()
    {
        return $this->app['config']->get('imageupload');
    }
}
