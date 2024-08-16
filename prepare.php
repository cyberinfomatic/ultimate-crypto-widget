<?php

require_once './vendor/autoload.php';

use Cyberinfomatic\UltimateCryptoWidget\Controllers\WidgetPostType;


// check if wp __ function exisit if resolve create a mock function
if (!function_exists('__')) {
	function __($string, $textDomain = 'default', ...$args)
	{
		return $string;
	}
}

function copyDirectory($src, $dest, $excludePaths): void {

	// echo starting to copy with blue color text
	echo "\033[34m Start Copying $src to $dest \033[0m\n";

	// get current working dir
	$pwd = getcwd();


	// if dir is in exclude path then skip the directory
	if (in_array(realpath($src), $excludePaths) || in_array(strtolower($src), $excludePaths)) {
		// print in red color text
		echo "\033[31m Skipping $src because it is in exclude path as $src \033[0m\n";
		return;
	}

    if (!is_dir($src)) {
        echo "Source directory $src does not exist.\n";
        return;
    }

    $dir = opendir($src);
    if (!$dir) {
        echo "Failed to open directory $src.\n";
        return;
    }

    @mkdir($dest, 0755, true);
    while (($file = readdir($dir)) !== false) {
        $srcPath = $src . '/' . $file;
        $destPath = $dest . '/' . $file;
		// echo trying to copy with yellow color text
        echo "\033[33m Copying $srcPath to $destPath \033[0m\n";

		// if the child or its parent file is in the exclude path then skip the file
        if (in_array(realpath($srcPath), $excludePaths) || in_array(strtolower($srcPath), $excludePaths)) {
			echo "\033[31m Skipping sub dir/path $srcPath because it is in exclude path as $srcPath \033[0m\n";
            continue;
        }

        if ($file == '.' || $file == '..') {
            continue;
        }

        if (is_dir($srcPath)) {
            copyDirectory($srcPath, $destPath, $excludePaths);
        } else {
            copy($srcPath, $destPath);
        }
		// echo copied with green color text
		echo "\033[32m Copied $srcPath to $destPath \033[0m\n";
    }
    closedir($dir);
}

function prepareZip($version, $config): void {
    $srcDir = realpath(__DIR__ . '/');
    if (!$srcDir) {
        echo "Failed to determine source directory.\n";
        return;
    }

    $destDir = __DIR__ . '/dist' . '/' . $version;
    echo "destDir: $destDir\n";
    mkdir($destDir, 0755, true);

    $excludePaths = array_map('realpath', array_merge($config['exclude'][$version] ?? [], $config['exclude']['both'] ?? []));
	$excludePaths = array_filter($excludePaths);
//	$excludePaths = array_map('strtolower', $excludePaths);
	// loop and print all exclude paths
	foreach ($excludePaths as $excludePath) {
		echo "\033[35m Exclude Path: $excludePath \033[0m\n";
	}
    copyDirectory($srcDir, $destDir, $excludePaths);
}

function zipDirectory($source, $destination): bool {
    if (!extension_loaded('zip') || !file_exists($source)) {
        echo "ZIP extension is not loaded or source directory ($source) does not exist.\n ";
        return false;
    }

    $zip = new ZipArchive();
    if (!$zip->open($destination, ZipArchive::CREATE)) {
        echo "Failed to create ZIP file $destination.\n";
        return false;
    }

    $source = str_replace('\\', '/', realpath($source));
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

    foreach ($files as $file) {
        $file = str_replace('\\', '/', $file);

        // Ignore "." and ".." folders
        if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) {
            continue;
        }

        $file = realpath($file);
        if (!$file) {
            continue;
        }

        if (is_dir($file)) {
            $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
        } elseif (is_file($file)) {
            $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
        }
    }

    return $zip->close();
}

function getProWidgetPaths(): array {
    $widget_types = WidgetPostType::WidgetTypes();
    $widget_type_view_path = 'src/Views/widgets';
    $pro_paths = [];
    $all_views = [];
    $view_with_free = [];

    foreach ($widget_types as $widget_type) {
        $isPro = $widget_type['pro'];
        $view = $widget_type['view'];
        $all_views[] = $view;
        $card = $widget_type['card'];
        if ($isPro) {
            $pro_paths[] = "$widget_type_view_path/$view/cards/$card.php";
            continue;
        }
        $view_with_free[] = $view;
    }

    // For views that do not have any free version, add them to pro paths
    $pro_only_views = array_diff($all_views, $view_with_free);
    $pro_only_react_view = array_map(fn($view) =>  "assets/react-build/widgets/$view", $pro_only_views);
    $pro_only_react_css_view = array_map(fn($view) =>  "assets/react-build/widgets/$view.css", $pro_only_views);
    $pro_only_react_css_rtl_view = array_map(fn($view) =>  "assets/react-build/widgets/$view-rtl.css", $pro_only_views);
    $pro_only_php_view = array_map(fn($view) => "$widget_type_view_path/$view", $pro_only_views);
    $final_pro_only_view = [...$pro_only_react_view, ...$pro_only_php_view, ...$pro_only_react_css_view, ...$pro_only_react_css_rtl_view];
    return array_unique(array_merge($pro_paths, $final_pro_only_view));
}

$config = json_decode(file_get_contents(__DIR__ . '/wordpress-include.json'), true);
if (!$config) {
    echo "Failed to parse wordpress-include.json.\n";
    exit(1);
}

$config['exclude']['free'] = array_merge($config['exclude']['free'] ?? [], getProWidgetPaths());
$version = $argv[1] ?? '';

if (!in_array($version, ['free', 'pro'])) {
    echo "Invalid version specified. Use 'free' or 'pro'.\n";
    exit(1);
}

//exit();
prepareZip($version, $config);
if (zipDirectory(__DIR__ . '/dist/' . $version, __DIR__ . '/dist/plugin-' . $version . '.zip')) {
    echo ucfirst($version) . " version ZIP created successfully.\n";
} else {
    echo "Failed to create " . ucfirst($version) . " version ZIP.\n";
}