<?php
/**
 * @param $defaultCfgFile
 * @param $newCfgFile
 */
function createConfigFileAndDie($baseConfigFile, $configFile)
{
    echo "No config file found [". $configFile ."].\n";
    $createFile = ($p = strtolower(prompt('Would you like to create it? [Y/n]: ')) == 'n') ? false : true;
    if (!$createFile) {
        die();
    }

    echo "\n** Creation of config file for naopal deployment **\n";
    $content = file_get_contents($baseConfigFile);
    $dir = dirname($configFile);
    if (!is_writable($dir)) {
        echo "\nWARNING: [". $dir ."] must writable directory. Can't create it automatically.";
        $tmpCfgFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($configFile);
        file_put_contents($tmpCfgFile, $content);

        echo "\nConfig file is now available at ". $tmpCfgFile;
        echo "\nPlease copy it to $configFile on this computer to validate configuration permanently (or run this script again to change parameters):";
        echo "\n\n> cp \"$tmpCfgFile\" \"$configFile\"\n\n";
        die();
    } else {
        file_put_contents($configFile, $content);
        echo "\nNew config file generate with success ! \n\n";
    }
}

/**
 * @param string $configFile
 */
function checkParameters($configFile)
{
    if (!file_exists($configFile)) {
        die('Config file not exist ['. $configFile .'].');
    }
    $configFileContent = file_get_contents($configFile);
    preg_match_all('/\{\{(.+)\}\((|.+)\)\[(|.+)\]:(.+)\}/', $configFileContent, $matches);

    $nbParameters = count($matches[0]);
    if ($nbParameters === 0) {
        return;
    }

    echo "To continue you need to define a few parameters.\n";
    echo "You will also be able to edit them later on.\n\n";
    echo "\n";

    for ($i = 0; $i < $nbParameters; $i++) {
        $prompt = null;
        $expectedValues = '';
        if (!empty($matches[2][$i])) {
            $expectedValues = ($matches[2][$i] === '*') ? 'required' : explode('|', $matches[2][$i]);
        }
        $defaultValue   = $matches[3][$i];
        $message        = $matches[4][$i];
        $expected       = !empty($expectedValues) && count($expectedValues) > 1 ? ' ('. implode('|', $expectedValues) . ')' : '';
        $default        = !empty($defaultValue) ? ' ['. $defaultValue .']' : '';

//        dump($expected);
//        dump($default);

        if (empty($expectedValues) && empty($defaultValue)) {
            $prompt = prompt($message . $expected . $default . ': ');
            $prompt = !empty($prompt) ? $prompt : '~';
        } else {
            //dump($expectedValues);
            while (empty($prompt)) {
                $prompt = prompt($message . $expected . $default . ': ');
                if (isset($defaultValue)) {
                    $prompt = !empty($prompt) ? $prompt : $defaultValue;
                }
                if (is_array($expectedValues) && !in_array($prompt, $expectedValues)) {
                    $prompt = null;
                    echo "\t- Not expected value: ". $expected . "\n";
                } elseif (empty($prompt)) {
                    echo "\t- Value is required\n";
                }
            }
        }


        //dump($prompt);

        $configFileContent = str_replace($matches[0][$i], $prompt, $configFileContent);
        //dump($expectedValues);
        //dump($defaultValue);
    }
    //dump($configFileContent);

    file_put_contents($configFile, $configFileContent);
}

/**
 * Ask question and return response
 * @param string $question
 *
 * @return string
 */
function prompt($question)
{
    echo $question;
    $handle = fopen ("php://stdin","r");
    return trim(fgets($handle));
}

/**
 * Switch between runLocally and run based on the server
 * @param  Server $server Server
 * @param  String $cmd    Command to execute
 * @return run
 */
function runInContext($server, $cmd)
{
    if ($server instanceof \Deployer\Server\Local) {
        return runLocally($cmd);
    } else {
        return run($statusCmd);
    }
}

function getDistantPath()
{
    $dst    = env('rsync_dest');
    while (is_callable($dst)) {
        $dst = $dst();
    }

    return $dst;
}

/**
 * Check on target server (local or remote) if directory exists
 * Return bool
 */
function isDir($dirName)
{
    return run('if [ -d "'.$dirName.'" ] ; then echo "true"; fi')->toBool();
}
/**
 * Check on target server (local or remote) if file exists
 * Return bool
 */
function fileExist($fileName)
{
    return run('if [ -f "'.$fileName.'" ] ; then echo "true"; fi')->toBool();
}
/**
 * Try to run command. If an error occures, return false
 */
function tryRun($cmd)
{
    try {
        return run($cmd);
    } catch (Exception $e) {
        return false;
    }
}
