<?php

namespace Tests\Assets;

use Statamic\API;
use Carbon\Carbon;
use Tests\TestCase;
use Statamic\Assets\Asset;
use Statamic\Fields\Blueprint;
use Illuminate\Http\UploadedFile;
use Statamic\Assets\AssetContainer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Tests\PreventSavingStacheItemsToDisk;

class AssetTest extends TestCase
{
    use PreventSavingStacheItemsToDisk;

    public function setUp()
    {
        parent::setUp();
        $this->tempDir = __DIR__.'/tmp';
    }

    public function tearDown()
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->tempDir);
    }

    /** @test */
    function it_sets_and_gets_data_values()
    {
        $asset = new Asset;
        $this->assertNull($asset->get('foo'));

        $return = $asset->set('foo', 'bar');

        $this->assertEquals($asset, $return);
        $this->assertTrue($asset->has('foo'));
        $this->assertEquals('bar', $asset->get('foo'));
        $this->assertEquals('fallback', $asset->get('unknown', 'fallback'));
    }

    /** @test */
    function it_gets_and_sets_data_values_using_magic_properties()
    {
        $asset = new Asset;
        $this->assertNull($asset->foo);

        $asset->foo = 'bar';

        $this->assertTrue($asset->has('foo'));
        $this->assertEquals('bar', $asset->foo);
    }

    /** @test */
    function it_gets_and_sets_all_data()
    {
        $asset = new Asset;
        $this->assertEquals([], $asset->data());

        $return = $asset->data(['foo' => 'bar']);

        $this->assertEquals($asset, $return);
        $this->assertEquals(['foo' => 'bar'], $asset->data());
    }

    /** @test */
    function it_sets_and_gets_the_container()
    {
        $asset = new Asset;
        $this->assertNull($asset->container());

        $return = $asset->container($container = new AssetContainer);

        $this->assertEquals($asset, $return);
        $this->assertEquals($container, $asset->container());
    }

    /** @test */
    function it_gets_the_container_if_provided_with_a_string()
    {
        API\AssetContainer::shouldReceive('find')
            ->with('test')
            ->andReturn($container = new AssetContainer);

        $asset = (new Asset)->container('test');

        $this->assertEquals($container, $asset->container());
    }

    /** @test */
    function it_gets_the_container_id()
    {
        $asset = (new Asset)->container(
            $container = (new AssetContainer)->handle('test')
        );

        $this->assertEquals('test', $asset->containerId());
    }

    /** @test */
    function it_gets_and_sets_the_path()
    {
        $asset = new Asset;
        $this->assertNull($asset->path());

        $return = $asset->path('path/to/asset.jpg');

        $this->assertEquals($asset, $return);
        $this->assertEquals('path/to/asset.jpg', $asset->path());
    }

    /** @test */
    function it_gets_the_id_from_the_container_and_path()
    {
        $asset = (new Asset)
            ->container((new AssetContainer)->handle('123'))
            ->path('path/to/asset.jpg');

        $this->assertEquals('123::path/to/asset.jpg', $asset->id());
    }

    /** @test */
    function it_gets_the_disk_from_the_container()
    {
        $container = $this->mock(AssetContainer::class);
        $container->shouldReceive('disk')->andReturn('test');

        $asset = (new Asset)->container($container);

        $this->assertEquals('test', $asset->disk());
    }

    /** @test */
    function it_gets_the_filename()
    {
        $this->assertEquals('asset', (new Asset)->path('path/to/asset.jpg')->filename());
    }

    /** @test */
    function it_gets_the_basename()
    {
        $this->assertEquals('asset.jpg', (new Asset)->path('path/to/asset.jpg')->basename());
    }

    /** @test */
    function it_gets_the_folder_name()
    {
        $this->assertEquals('path/to', (new Asset)->path('path/to/asset.jpg')->folder());
    }

    /** @test */
    function it_gets_the_resolved_path()
    {
        $container = $this->mock(AssetContainer::class);
        $container->shouldReceive('diskPath')->andReturn('path/to/container');

        $asset = (new Asset)->container($container)->path('path/to/asset.jpg');

        $this->assertEquals('path/to/container/path/to/asset.jpg', $asset->resolvedPath());
    }

    /** @test */
    function it_gets_the_extension()
    {
        $this->assertEquals('jpg', (new Asset)->path('asset.jpg')->extension());
        $this->assertEquals('txt', (new Asset)->path('asset.txt')->extension());
        $this->assertNull((new Asset)->path('asset')->extension());
    }

    /** @test */
    function it_checks_if_an_extension_matches()
    {
        $asset = (new Asset)->path('asset.jpg');

        $this->assertTrue($asset->extensionIsOneof(['jpg']));
        $this->assertTrue($asset->extensionIsOneof(['jpg', 'txt']));
        $this->assertFalse($asset->extensionIsOneof(['txt', 'mp3']));
    }

    /** @test */
    function it_checks_if_its_an_audio_file()
    {
        $extensions = ['aac', 'flac', 'm4a', 'mp3', 'ogg', 'wav'];

        foreach ($extensions as $ext) {
            $this->assertTrue((new Asset)->path("path/to/asset.$ext")->isAudio());
        }

        $this->assertFalse((new Asset)->path("path/to/asset.jpg")->isAudio());
    }

    /** @test */
    function it_checks_if_its_a_video_file()
    {
        $extensions = ['h264', 'mp4', 'm4v', 'ogv', 'webm'];

        foreach ($extensions as $ext) {
            $this->assertTrue((new Asset)->path("path/to/asset.$ext")->isVideo());
        }

        $this->assertFalse((new Asset)->path("path/to/asset.jpg")->isVideo());
    }

    /** @test */
    function it_checks_if_its_an_image_file()
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif'];

        foreach ($extensions as $ext) {
            $this->assertTrue((new Asset)->path("path/to/asset.$ext")->isImage());
        }

        $this->assertFalse((new Asset)->path("path/to/asset.txt")->isImage());
    }

    /** @test */
    function it_checks_if_it_can_be_previewed_in_google_docs_previewer()
    {
        $extensions = [
            'doc', 'docx', 'pages', 'txt', 'ai', 'psd', 'eps', 'ps', 'css', 'html', 'php', 'c', 'cpp', 'h', 'hpp', 'js',
            'ppt', 'pptx', 'flv', 'tiff', 'ttf', 'dxf', 'xps', 'zip', 'rar', 'xls', 'xlsx',
        ];

        foreach ($extensions as $ext) {
            $this->assertTrue((new Asset)->path("path/to/asset.$ext")->isPreviewable());
        }

        $this->assertFalse((new Asset)->path("path/to/asset.jpg")->isPreviewable());
    }

    /** @test */
    function it_gets_last_modified_time()
    {
        $container = $this->tempContainer();
        $now = Carbon::now();
        touch($this->tempDir . '/test.txt', $now->timestamp);

        $asset = (new Asset)->container($container)->path('test.txt');

        $lastModified = $asset->lastModified();
        $this->assertInstanceOf(Carbon::class, $lastModified);
        $this->assertEquals($now->timestamp, $lastModified->timestamp);
    }

    /** @test */
    function it_saves()
    {
        $asset = new Asset;
        $container = $this->spy(AssetContainer::class);
        $container->shouldReceive('addAsset')->once()->with($asset)->andReturn($container);
        $container->shouldReceive('save')->once();
        $asset->container($container);

        $return = $asset->save();

        $this->assertEquals($asset, $return);

        // TODO: Assert about event, or convert to a callback
    }

    /** @test */
    function it_deletes()
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->put('path/to/asset.txt', '');
        $container = API\AssetContainer::make('test')->disk('local');
        API\AssetContainer::shouldReceive('save')->with($container);
        $asset = (new Asset)->container($container)->path('path/to/asset.txt');
        $container->addAsset($asset);
        $disk->assertExists('path/to/asset.txt');

        $return = $asset->delete();

        $this->assertEquals($asset, $return);
        $disk->assertMissing('path/to/asset.txt');

        // TODO: Assert about event, or convert to a callback
    }

    /** @test */
    function it_can_be_moved_to_another_folder()
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->put('old/asset.txt', 'The asset contents');
        $container = API\AssetContainer::make('test')->disk('local');
        API\AssetContainer::shouldReceive('save')->with($container);
        $asset = (new Asset)->container($container)->path('old/asset.txt')->data(['foo' => 'bar']);
        $container->addAsset($asset);
        $disk->assertExists('old/asset.txt');
        $this->assertEquals([
            'old/asset.txt' => ['foo' => 'bar']
        ], $container->assets('/', true)->map->data()->all());

        $return = $asset->move('new');

        $this->assertEquals($asset, $return);
        $disk->assertMissing('old/asset.txt');
        $disk->assertExists('new/asset.txt');
        $this->assertEquals([
            'new/asset.txt' => ['foo' => 'bar']
        ], $container->assets('/', true)->map->data()->all());
    }

    /** @test */
    function it_can_be_moved_to_another_folder_with_a_new_filename()
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->put('old/asset.txt', 'The asset contents');
        $container = API\AssetContainer::make('test')->disk('local');
        API\AssetContainer::shouldReceive('save')->with($container);
        $asset = (new Asset)->container($container)->path('old/asset.txt')->data(['foo' => 'bar']);
        $container->addAsset($asset);
        $disk->assertExists('old/asset.txt');
        $this->assertEquals([
            'old/asset.txt' => ['foo' => 'bar']
        ], $container->assets('/', true)->map->data()->all());

        $return = $asset->move('new', 'newfilename');

        $this->assertEquals($asset, $return);
        $disk->assertMissing('old/asset.txt');
        $disk->assertExists('new/newfilename.txt');
        $this->assertEquals([
            'new/newfilename.txt' => ['foo' => 'bar']
        ], $container->assets('/', true)->map->data()->all());
    }

    /** @test */
    function it_renames()
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $disk->put('old/asset.txt', 'The asset contents');
        $container = API\AssetContainer::make('test')->disk('local');
        API\AssetContainer::shouldReceive('save')->with($container);
        $asset = (new Asset)->container($container)->path('old/asset.txt')->data(['foo' => 'bar']);
        $container->addAsset($asset);
        $disk->assertExists('old/asset.txt');
        $this->assertEquals([
            'old/asset.txt' => ['foo' => 'bar']
        ], $container->assets('/', true)->map->data()->all());

        $return = $asset->rename('newfilename');

        $this->assertEquals($asset, $return);
        $disk->assertMissing('old/asset.txt');
        $disk->assertExists('old/newfilename.txt');
        $this->assertEquals([
            'old/newfilename.txt' => ['foo' => 'bar']
        ], $container->assets('/', true)->map->data()->all());
    }

    /** @test */
    function it_gets_dimensions()
    {
        Storage::fake('local');
        Storage::disk('local')->putFileAs(
            'images',
            UploadedFile::fake()->image('test.jpg', 30, 60),
            'test.jpg'
        );

        $asset = (new Asset)
            ->container((new AssetContainer)->disk('local'))
            ->path('images/test.jpg');

        $this->assertEquals([30, 60], $asset->dimensions());
        $this->assertEquals(30, $asset->width());
        $this->assertEquals(60, $asset->height());
    }

    /** @test */
    function it_gets_file_size_in_bytes()
    {
        $container = $this->tempContainer();
        $size = filesize($fixture = __DIR__.'/__fixtures__/container/a.txt');
        copy($fixture, $this->tempDir.'/test.txt');

        $asset = (new Asset)
            ->container($container)
            ->path('test.txt');

        $this->assertEquals($size, $asset->size());
    }

    /** @test */
    function it_converts_to_array()
    {
        API\Blueprint::shouldReceive('find')->once()
            ->with('custom')
            ->andReturn((new Blueprint)->setHandle('custom'));

        $container = (new AssetContainer)
            ->handle('test_container')
            ->blueprint('custom');

        $asset = (new Asset)
            ->container($container)
            ->set('title', 'test')
            ->setSupplement('foo', 'bar')
            ->path('path/to/asset.jpg');

        $array = $asset->toArray();

        $this->assertArraySubset([
            'id' => 'test_container::path/to/asset.jpg',
            'title' => 'test',
            'path' => 'path/to/asset.jpg',
            'filename' => 'asset',
            'basename' => 'asset.jpg',
            'extension' => 'jpg',
            'is_asset' => true,
            'folder' => 'path/to',
            'container' => 'test_container',
            // 'value' => '?',
            'blueprint' => 'custom',
            'foo' => 'bar',
        ], $array);

        $keys = ['is_audio', 'is_previewable', 'is_image', 'is_video', 'edit_url'];
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array);
        }

        foreach ($this->toArrayKeysWhenFileExists() as $key) {
            $this->assertArrayNotHasKey($key, $array);
        }
    }

    /** @test */
    function data_keys_get_added_to_array()
    {
        $array = (new Asset)
            ->container((new AssetContainer)->handle('test'))
            ->set('title', 'test')
            ->path('path/to/asset.jpg')
            ->set('foo', 'bar')
            ->set('bar', 'baz')
            ->toArray();

        $this->assertEquals('bar', $array['foo']);
        $this->assertEquals('baz', $array['bar']);
    }

    /** @test */
    function extra_keys_get_added_to_array_when_file_exists()
    {
        $container = $this->tempContainer();
        $size = filesize($fixture = __DIR__.'/__fixtures__/container/a.txt');
        copy($fixture, $this->tempDir.'/test.txt');

        $asset = (new Asset)->container($container)->path('test.txt');

        $array = $asset->toArray();
        foreach ($this->toArrayKeysWhenFileExists() as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    /** @test */
    function it_can_upload_a_file()
    {
        $this->markTestIncomplete();
    }

    /** @test */
    function it_can_replace_the_file()
    {
        $this->markTestIncomplete();
    }

    /** @test */
    function it_gets_the_url()
    {
        $container = $this->mock(AssetContainer::class);
        $container->shouldReceive('private')->andReturnFalse();
        $container->shouldReceive('url')->andReturn('http://example.com');
        $asset = (new Asset)->container($container)->path('path/to/test.txt');

        $this->assertEquals('http://example.com/path/to/test.txt', $asset->url());
    }

    /** @test */
    function there_is_no_url_for_a_private_asset()
    {
        $container = $this->mock(AssetContainer::class);
        $container->shouldReceive('private')->andReturnTrue();
        $asset = (new Asset)->container($container)->path('path/to/test.txt');

        $this->assertNull($asset->url());
    }

    private function toArrayKeysWhenFileExists()
    {
        return [
            'size', 'size_bytes', 'size_kilobytes', 'size_megabytes', 'size_gigabytes',
            'size_b', 'size_kb', 'size_mb', 'size_gb',
            'last_modified', 'last_modified_timestamp', 'last_modified_instance',
            'focus_css',
        ];
    }

    private function tempContainer()
    {
        config(['filesystems.disks.temp' => [
            'driver' => 'local',
            'root' => $this->tempDir,
        ]]);

        @mkdir($this->tempDir);

        return (new AssetContainer)->disk('temp');
    }
}