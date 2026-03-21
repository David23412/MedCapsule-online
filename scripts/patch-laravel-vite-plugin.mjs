import fs from 'node:fs';
import path from 'node:path';

const projectRoot = process.cwd();
const pluginDistDir = path.join(projectRoot, 'node_modules', 'laravel-vite-plugin', 'dist');
const indexFile = path.join(pluginDistDir, 'index.js');
const devServerIndexFile = path.join(pluginDistDir, 'dev-server-index.html');

if (!fs.existsSync(indexFile) || !fs.existsSync(devServerIndexFile)) {
    process.exit(0);
}

const replaceIfPresent = (source, searchValue, replaceValue) => {
    if (!source.includes(searchValue)) {
        return source;
    }

    return source.replace(searchValue, replaceValue);
};

let indexContents = fs.readFileSync(indexFile, 'utf8');
indexContents = replaceIfPresent(
    indexContents,
    '            server.config.logger.info(`  ${colors.green("\\u279C")}  ${colors.bold("APP_URL")}: ${colors.cyan(appUrl.replace(/:(\\d+)/, (_, port) => `:${colors.bold(port)}`))}`);\n',
    ''
);
fs.writeFileSync(indexFile, indexContents);

let devServerIndexContents = fs.readFileSync(devServerIndexFile, 'utf8');
devServerIndexContents = replaceIfPresent(
    devServerIndexContents,
    `                        <div class="pt-8 text-base leading-7">
                            <p>
                                Your Laravel application's configured <code class="text-sm font-bold text-gray-900 dark:text-gray-100">APP_URL</code> is:<br />
                                <a href="{{ APP_URL }}" class="font-semibold text-red-500 hover:text-red-600">{{ APP_URL }}</a>
                            </p>
                            <p class="mt-6">Want more information on Laravel's Vite integration?</p>
                            <p>
                                <a href="https://laravel.com/docs/vite" class="font-semibold text-red-500 hover:text-red-600">Read the docs &rarr;</a>
                            </p>
                        </div>
`,
    `                        <div class="pt-8 text-base leading-7">
                            <p>Want more information on Laravel's Vite integration?</p>
                            <p class="mt-6">
                                <a href="https://laravel.com/docs/vite" class="font-semibold text-red-500 hover:text-red-600">Read the docs &rarr;</a>
                            </p>
                        </div>
`
);
fs.writeFileSync(devServerIndexFile, devServerIndexContents);
