<?php

namespace Matriphe\Imageupload;

use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Str;
use Imagine\Imagick\Imagine as Imagick;
use Imagine\Gmagick\Imagine as Gmagick;
use Imagine\Gd\Imagine as Gd;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Metadata\ExifMetadataReader;
use Exception;

/*
use Illuminate\Support\Str;

use Config;
use File;
use Log;
*/

class Imageupload implements ImageuploadInterface
{
    /**
     * imagine.
     * 
     * @var Imagine
     */
    protected $imagine;

    /**
     * library.
     * 
     * (default value: 'gd')
     * 
     * @var string
     */
    protected $library = 'gd';

    /**
     * quality.
     * 
     * (default value: 90)
     * 
     * @var int
     */
    protected $quality = 90;

    /**
     * uploadpath.
     * 
     * (default value: null)
     * 
     * @var mixed
     */
    protected $uploadpath = null;

    /**
     * newfilename.
     * 
     * (default value: 'original')
     * 
     * @var string
     */
    protected $newfilename = 'original';

    /**
     * dimensions.
     * 
     * (default value: [])
     * 
     * @var mixed
     */
    protected $dimensions = [];

    /**
     * suffix.
     * 
     * (default value: true)
     * 
     * @var bool
     */
    protected $suffix = true;

    /**
     * exif.
     * 
     * (default value: false)
     * 
     * @var bool
     */
    protected $exif = false;

    /**
     * config.
     * 
     * @var mixed
     */
    protected $config;

    /**
     * results.
     * 
     * (default value: [])
     * 
     * @var mixed
     */
    public $results = [];

    /**
     * __construct function.
     * 
     * @param string $library
     * @param mixed  $quality
     * @param mixed  $uploadpath
     * @param mixed  $newfilename
     * @param mixed  $dimensions
     * @param mixed  $suffix
     * @param mixed  $exif
     */
    public function __construct(
        $library,
        $quality,
        $uploadpath,
        $newfilename,
        $dimensions,
        $suffix,
        $exif
    ) {
        $this->library = $library;
        $this->quality = $quality;
        $this->uploadpath = $uploadpath;
        $this->newfilename = $newfilename;
        $this->dimensions = $dimensions;
        $this->suffix = $suffix;
        $this->exif = $exif;

        switch ($this->library) {
            case 'imagick';
                $this->imagine = new Imagick();
                break;
            case 'gmagick';
                $this->imagine = new Gmagick();
                break;
            default:
                $this->imagine = new Gd();
        }
    }

    /**
     * checkPathIsOk function.
     * 
     * @param string $path
     * @param string $dir  (default: null)
     */
    private function checkPathIsOk($path, $dir = null)
    {
        $path = rtrim($path, '/').($dir ? '/'.trim($dir, '/') : '');

        $file = new File();
        if ($file->isDirectory($path) && $file->isWritable($path)) {
            return true;
        } else {
            try {
                @$file->makeDirectory($path, 0777, true);

                return true;
            } catch (Exception $e) {
                $this->results['error'] = $e->getMessage();

                return false;
            }
        }
    }

    /**
     * checkIsImage function.
     * 
     * @param UploadedFile $filesource
     */
    private function checkIsImage(UploadedFile $filesource)
    {
        if (substr($filesource->getMimeType(), 0, 5) == 'image') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * upload function.
     * 
     * @param UploadedFile $filesource
     * @param string       $newfilename (default: null)
     * @param string       $dir         (default: null)
     */
    public function upload($filesource, $newfilename = null, $dir = null)
    {
        $isPathOk = $this->checkPathIsOk($this->uploadpath, $dir);
        $isImage = $this->checkIsImage($filesource);

        if ($isPathOk && $filesource && $isImage) {
            if ($filesource) {
                $this->results['path'] = implode('', [
                    rtrim($this->uploadpath, '/'),
                    ($dir ? '/'.trim($dir, '/') : ''),
                ]);
                
                $this->results = array_merge($this->results, [
                    'dir' => str_replace(public_path().'/', '', $this->results['path']),
                    'original_filename' => $filesource->getClientOriginalName(),
                    'original_filepath' => $filesource->getRealPath(),
                    'original_extension' => $filesource->getClientOriginalExtension(),
                    'original_filesize' => $filesource->getSize(),
                    'original_mime' => $filesource->getMimeType(),
                    'exif' = $this->getExif($filesource->getRealPath()),
                ]);
                
                switch ($this->newfilename) {
                    case 'hash':
                        $generatedfilename = md5(json_encode([
                            $this->results['original_filename'],
                            $this->results['original_extension'],
                            strtotime('now')
                        ]));
                        break;
                    case 'random':
                        $str = new Str();
                        $generatedfilename = $str->random();
                        break;
                    case 'timestamp':
                        $generatedfilename = strtotime('now');
                        break;
                    case 'custom':
                        if (!empty($newfilename)) {
                            $generatedfilename = $newfilename;
                        } else {
                            $generatedfilename = $this->results['original_filename'];
                        }
                        break;
                    default:
                        $generatedfilename = $this->results['original_filename'];
                }
                
                $generatedfilename = preg_replace(
                    '/(\.'.$this->results['original_extension'].')$/i', 
                    '', 
                    $generatedfilename
                );
                
                $this->results['filename'] = implode('.', [
                    $generatedfilename,
                    $this->results['original_extension']
                ]);

                $uploaded = $filesource->move($this->results['path'], $this->results['filename']);
                if ($uploaded) {
                    list($width, $height) = getimagesize($this->results['original_filepath']);
                    $this->results = array_merge($this->results, [
                        'original_filepath' => implode('/', [
                            rtrim($this->results['path']),
                            $this->results['filename']
                        ]),
                        'original_filedir' => str_replace(
                            public_path().'/', 
                            '', $this->results['original_filepath']
                        ),
                        'basename' => pathinfo($this->results['original_filepath'], PATHINFO_FILENAME),
                        'original_width' => $width,
                        'original_height' => $height,
                    ]);

                    $this->createDimensions($this->results['original_filepath']);
                } else {
                    $this->results['error'] = 'File '.$this->results['original_filename '].' is not uploaded.';
                }
            }
        }

        return $this->results;
    }

    /**
     * createDimensions function.
     * 
     * @param string $filepath
     */
    protected function createDimensions($filepath)
    {
        if (!empty($this->dimensions) && is_array($this->dimensions)) {
            foreach ($this->dimensions as $name => $dimension) {
                $width = (int) $dimension[0];
                $height = isset($dimension[1]) ?  (int) $dimension[1] : $width;
                $crop = isset($dimension[2]) ? (bool) $dimension[2] : false;

                $this->resize($filepath, $name, $width, $height, $crop);
            }
        }
    }

    /**
     * resize function.
     * 
     * @param string $filepath
     * @param bool   $suffix
     * @param int    $width
     * @param int    $height
     * @param bool   $crop
     */
    private function resize($filepath, $suffix, $width, $height, $crop)
    {
        if (!$height) {
            $height = $width;
        }

        $suffix = trim($suffix);

        $path = $this->results['path'].($this->suffix == false ? '/'.trim($suffix, '/') : '');
        $name = implode('', [
            $this->results['basename'],
            ($this->suffix == true ? '_'.trim($suffix, '/') : ''),
            '.',
            $this->results['original_extension']
        ]);

        $pathname = implode('/', [$path, $name]);

        try {
            $isPathOk = $this->checkPathIsOk(
                $this->results['path'], 
                ($this->suffix == false ? $suffix : '')
            );

            if ($isPathOk) {
                $size = new Box($width, $height);
                $mode = $crop ? ImageInterface::THUMBNAIL_OUTBOUND : ImageInterface::THUMBNAIL_INSET;
                $newfile = $this->imagine
                    ->open($filepath)
                    ->thumbnail($size, $mode)
                    ->save($pathname, ['quality' => $this->quality]);

                list($nwidth, $nheight) = getimagesize($pathname);
                $filesize = filesize($pathname);

                $this->results['dimensions'][$suffix] = [
                    'path' => $path,
                    'dir' => str_replace(public_path().'/', '', $path),
                    'filename' => $name,
                    'filepath' => $pathname,
                    'filedir' => str_replace(public_path().'/', '', $pathname),
                    'width' => $nwidth,
                    'height' => $nheight,
                    'filesize' => $filesize,
                ];
            }
        } catch (Exception $e) {
        }
    }

    /**
     * getExif function.
     * 
     * @param string $filesourcepath
     */
    protected function getExif($filesourcepath)
    {
        $exifdata = null;

        if ($this->exif) {
            try {
                $image = $this->imagine
                ->setMetadataReader(new ExifMetadataReader())
                ->open($filesourcepath);
                $metadata = $image->metadata();
                $exifdata = $metadata->toArray();
            } catch (Exception $e) {
            }
        }

        return $exifdata;
    }
}
