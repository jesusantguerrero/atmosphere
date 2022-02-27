<?php

namespace Insane\Remotr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class GitSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insane:remotr setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set basic setup in remote server to handle deploys from local';

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
        $account = $this->argument('REMOTR_SERVER_IP');
        $name = getenv('REMOTR_REPO_NAME');
        $process = $this->getRemoteGitSetupProcess($account, $name);
        $callback = null;

        $callback = function ($type, $host, $line) {
            if (Str::startsWith($line, 'Warning: Permanently added ')) {
                return;
            }

            $this->displayOutput($type, $host, $line);
        };

        $process->run(function ($type, $output) use ($callback) {
            $callback($type, "Digital Ocean", $output);
        });

        $process->getExitCode();
        echo PHP_EOL;
        echo "copy this code in the repo: git remote add live ssh://root@$account/home/$name.git";
    }

    public function getRemoteGitSetupProcess($account, $repoName) {
        $process = Process::fromShellCommandline("ssh $account -T");

        $process->setInput(
            "cd /home
            mkdir $repoName.git
            cd $repoName.git
            git init --bare
            echo \"git --work-tree=/var/www/$repoName.com --git-dir=/home/$repoName.git checkout -f\" > hooks/post-receive
            chmod +x hooks/post-receive
            mkdir /var/www/$repoName.com
            cd /var/www/"
        );
        return $process;
    }


    protected function displayOutput($type, $host, $line)
    {
        $lines = explode("\n", $line);

        $hostColor = "<fg=cyan>[{$host}]</>";

        foreach ($lines as $line) {
            if (strlen(trim($line)) === 0) {
                continue;
            }

            if ($type == Process::OUT) {
                $this->output->write($hostColor.': '.trim($line).PHP_EOL);
            } else {
                $this->output->write($hostColor.':  '.'<fg=red>'.trim($line).'</>'.PHP_EOL);
            }
        }
    }
}
