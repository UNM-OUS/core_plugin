<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DigraphCMS\FS;
use DigraphCMS\Media\DeferredFile;
use Mpdf\QrCode\Output\Png;
use Mpdf\QrCode\Output\Svg;
use Mpdf\QrCode\QrCode;

class QrGenerator
{

    public static function svgFile(string $content, string $filename = 'qr', ?callable $permissions = null): DeferredFile
    {
        return new DeferredFile(
            $filename . '.svg',
            function (DeferredFile $file) use ($content) {
                FS::dump($file->path(), self::svgContent($content));
            },
            $content,
            -1,
            $permissions
        );
    }

    public static function svgContent(string $content): string
    {
        $qr = new QrCode($content);
        $output = new Svg();
        return $output->output($qr, 600);
    }

    public static function pngFile(string $content, string $filename = 'qr', ?callable $permissions = null): DeferredFile
    {
        return new DeferredFile(
            $filename . '.png',
            function (DeferredFile $file) use ($content) {
                FS::dump($file->path(), self::pngContent($content));
            },
            $content,
            -1,
            $permissions
        );
    }

    public static function pngContent(string $content): string
    {
        $qr = new QrCode($content);
        $output = new Png();
        return $output->output($qr, 600);
    }
}
