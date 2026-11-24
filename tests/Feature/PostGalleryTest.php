<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_cover_and_gallery_photos_open_in_the_photo_viewer_with_their_size(): void
    {
        $post = Post::factory()->create([
            'cover_image' => $this->storePhoto('noticias/portada.jpg', 1600, 900),
            'gallery' => [
                $this->storePhoto('noticias/galeria/reunion.jpg', 1200, 800),
                $this->storePhoto('noticias/galeria/visita.jpg', 600, 900),
            ],
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('data-photo-gallery', false)
            ->assertSee('data-pswp-width="1600" data-pswp-height="900"', false)
            ->assertSee('data-pswp-width="1200" data-pswp-height="800"', false)
            ->assertSee('data-pswp-width="600" data-pswp-height="900"', false)
            ->assertSee('Ampliar la foto 2 de 2 de la galería')
            ->assertSee('2 fotos')
            ->assertDontSee('en una pestaña nueva');
    }

    public function test_a_cover_also_added_to_the_gallery_is_shown_once(): void
    {
        $cover = $this->storePhoto('noticias/portada.jpg', 800, 600);
        $other = $this->storePhoto('noticias/galeria/campo.jpg', 800, 600);
        $post = Post::factory()->create(['cover_image' => $cover, 'gallery' => [$cover, $other]]);

        $this->assertSame([Storage::disk('public')->url($other)], array_column($post->galleryPhotos(), 'url'));

        $this->get(route('posts.show', $post))->assertOk()->assertSee('1 foto');
    }

    public function test_portrait_camera_photos_keep_the_orientation_browsers_show(): void
    {
        $path = $this->storePhoto('noticias/vertical.jpg', 400, 300, exifOrientation: 6);
        $post = Post::factory()->create(['cover_image' => $path]);

        $this->assertSame(['width' => 300, 'height' => 400], array_intersect_key($post->coverPhoto(), array_flip(['width', 'height'])));
    }

    public function test_a_missing_photo_file_does_not_break_the_gallery(): void
    {
        $post = Post::factory()->create(['gallery' => ['noticias/galeria/no-existe.jpg']]);

        $this->assertSame([['url' => Storage::disk('public')->url('noticias/galeria/no-existe.jpg'), 'width' => null, 'height' => null]], $post->galleryPhotos());

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Ampliar la foto 1 de 1 de la galería')
            ->assertDontSee('data-pswp-width', false);
    }

    /**
     * Stores a JPEG on the fake public disk, optionally tagged with an EXIF orientation like a phone camera does.
     */
    private function storePhoto(string $path, int $width, int $height, ?int $exifOrientation = null): string
    {
        $jpeg = UploadedFile::fake()->image(basename($path), $width, $height)->getContent();

        if ($exifOrientation !== null) {
            $tiff = 'II'.pack('vV', 42, 8).pack('vvvVvvV', 1, 0x0112, 3, 1, $exifOrientation, 0, 0);
            $exif = "Exif\0\0".$tiff;
            $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);
        }

        Storage::disk('public')->put($path, $jpeg);

        return $path;
    }
}
