<?php

namespace PipeDream\LaravelCreate\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;

class PipeDreamBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faster:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild Faster from source files';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info("Rebuilding Faster...");

        $appJSPath = __DIR__ . "/../resources/js/app.js";
        $appJSOriginal = file_get_contents(__DIR__ . "/../resources/js/app_backup.js");
        $PDFolder = __DIR__ . "/../../";
        $app = file_get_contents($appJSPath);
        $nodeModules = base_path() . DIRECTORY_SEPARATOR . "node_modules";

        $level1 = Collect(glob($nodeModules . DIRECTORY_SEPARATOR . "*/package.json"));
        $level2 = Collect(glob($nodeModules . DIRECTORY_SEPARATOR . "*/*/package.json"));
        $modules = $level1->merge($level2);

        $this->info("\nDiscovering Faster packages");
        $bar = $this->output->createProgressBar();
        $bar->start();

        $fasterModules = [];
        $master = [];
        foreach ($modules as $module) {
            $bar->advance();
            $p = json_decode(file_get_contents($module), true);
            if (!array_key_exists('PipeDream', $p)) continue;
            if (array_key_exists('PipeDreamMaster', $p) && $p['PipeDreamMaster'] === true) {
                $master[rtrim($module, "/package.json")] = $p["PipeDream"];
                continue;
            }
            $fasterModules[rtrim($module, "/package.json")] = $p["PipeDream"];
        }

        $fasterModules = array_merge($master, $fasterModules);

        $bar->finish();
        $this->info("\n");
        if (count($fasterModules) === 0) {
            $this->error("No Faster modules found!");
            return;
        }
        foreach ($fasterModules as $fasterModule) {
            $this->info("Discovered package: " . $fasterModule);
        }
        $overwritten = str_replace("/* file factories will be automatically added */", implode(", ", $fasterModules), $app);
        array_walk($fasterModules, function (&$v, $path) {
            $v = 'import {' . $v . '} from "' . str_replace('\\', '/', $path) . '"';
        });
        $overwritten = str_replace("/* imports will be automatically added */", implode(";\n", $fasterModules), $overwritten);

        file_put_contents($appJSPath, $overwritten);

        if(!file_exists($PDFolder . "node_modules")){
            $this->info("Building Faster Node modules...");
            exec("cd " . $PDFolder . " && npm install");
        }

        $this->info("Compiling...");
        if (!exec("cd " . $PDFolder . " && npm run dev")) {
            $this->info("!!!Something went wrong when compiling!!!");
            file_put_contents($appJSPath, $app);
            exit(0);
        }
        $this->info("Faster was built successfully, go build something awesome!");
        // set app.js back to the template for the next update
        file_put_contents($appJSPath, $appJSOriginal);
    }
}
