<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UnixInstaller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unix {do=install}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Unix theme for Pterodactyl';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        switch ($this->argument('do')) {
          case 'install':
            $this->install('request');
            break;
          case 'remove':
            $this->uninstall();
            break;
          case 'restore':
            $this->restore();
            break;
          default:
          $this->install('request');
          break;
        }
    }

    // Function licenseInstall() no longer in use due to error, is set to be fixed.
    private function licenseInstall()
    {
		$setting = array();//new UnixSetting();
		$data = array();
		foreach ($setting->all() as $key => $value) {
			$data[$value->name] = $value->value;
		}

        $key = (isset($data['l_key']) ? $data['l_key'] : 'U_123');
        $serverip = $this->command('curl ifconfig.co/ip 2>/dev/null');
		$serverurl = parse_url(config('app.url'))['host'];
		$lmc = 'lic'.'ense';

		$upd = Http::get('https://vertisanpro.com/api/unix/'.$lmc.'/'.$key.'/'.$serverip.'/'.$serverurl)->object();
        
		if (isset($upd->resp) AND $upd->resp == true)
		{
            $this->install($key);
        } else {
            $this->install('request');
        }
    }

    private function install($license)
    {
        if (version_compare(config('app.version'), '1.8.1') < 0) {
            $this->error('Cannot proceed with installation, Unix requires Pterodactyl 1.8.1 or above, you have [' . config('app.version') . '].');
            return 0;
        }

        $this->info('+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=');
        $this->info('Unix Installation Started');
        $this->info('Theme by Mubeen & Gigabait');
        $this->info('+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=');

        $this->newLine();
        
        if($license == 'request') {
            $this->info('Type "cancel" to stop.');
            $key = $this->ask('Enter Unix License key');
            
            if($key == "cancel") {
                $this->info('Successfully cancelled Unix Installation.');
                return 0;
            }

             } else {

            $this->info('Automatically retrieved license '.$license);

            if (!$this->confirm('Do you wish to continue installation?', true)) {
                $this->info('Successfully cancelled Unix Installation.');
                return 0;
            }
            $key = $license;
        }
        
        $serverip = $this->command('curl ifconfig.co/ip 2>/dev/null');
		$serverurl = parse_url(config('app.url'))['host'];
		$lmc = 'lic'.'ense';

		$upd = Http::get('https://vertisanpro.com/api/unix/'.$lmc.'/'.$key.'/'.$serverip.'/'.$serverurl)->object();
		
		if (isset($upd->resp) AND $upd->resp == true)
		{

            $this->info('Successfully validated license key, proceeding with installation...');

            $this->info('Downloaded Zip file & extracting');
            $this->command("wget -O UnixLatest.zip https://vertisanpro.com/api/unix/$lmc/$key/$serverip/$serverurl/stable/latest/true -o /dev/null");
            $this->command('unzip -o UnixLatest.zip > /dev/null 2>&1 &');
            $this->command('rm UnixLatest.zip > /dev/null 2>&1 &');

            $this->info('Migrate database');
            $this->command('php artisan migrate --force');
            $this->command('php artisan view:clear && php artisan config:clear');

            $this->info("Running Yarn (This can take a few minutes)");
            $this->command('yarn && yarn build:production');

            $this->newLine(3);
            $this->info('+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=');
            $this->info('Thanks for purchasing Unix, '.$upd->license_buyer);
            $this->info('We have successfully installed the theme!');
            $this->info('+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=');

		} else {
            $this->error($upd->text);
        }
    }

    private function uninstall()
    {
        $this->info('Updating Pterodactyl to the latest version');
  
            /**
             * Commences update proccess and 
             * executes commands below into terminal. 
             */
  
            $this->command('php artisan down');
            $this->command('cd '.base_path());
            $this->command('curl -L https://github.com/pterodactyl/panel/releases/latest/download/panel.tar.gz | tar -xzv');
            $this->command('chmod -R 755 storage/* bootstrap/cache');
            $this->command('echo \"yes\" | composer install --no-dev --optimize-autoloader');
            $this->command('php artisan view:clear && php artisan config:clear');
            $this->command('php artisan migrate --seed --force');
  
            $this->command('chown -R www-data:www-data '.base_path().'/*');
            $this->command('chown -R nginx:nginx '.base_path().'/*');
            $this->command('chown -R apache:apache '.base_path().'/*');
  
  
            $this->command('php artisan queue:restart');
            $this->command('php artisan up');
            $this->info('Update Complete - Successfully Installed the latest version of Pterodactyl Panel!');
        
    }

    private function restore()
    {
        
    $this->info('Restore Started... We will now proceed to do a Clean Install of the Unix theme.');
    $this->info('During the restore method, addons/themes will be removed.');

    if ($this->confirm('Do you wish to continue?', true)) {
        
        $this->command('rm -r '.base_path().'/resources/scripts');
        $this->uninstall();
        $this->install('request');
    }

    }

    /**
     * executes commands into terminal
     */
    private function command($cmd)
    {
      return exec($cmd);
    }

}
