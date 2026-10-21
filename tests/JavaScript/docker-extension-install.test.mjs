import {test} from 'node:test';
import assert from 'node:assert/strict';
import {mkdtempSync,writeFileSync,readFileSync,rmSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join,resolve} from 'node:path';
import {spawnSync} from 'node:child_process';
function run(failures) {
    const directory=mkdtempSync(join(tmpdir(),'medcapsule-extension-'));
    try {
        writeFileSync(join(directory,'pecl'),`#!/bin/sh\nfile="$EXTENSION_TEST_DIR/$2"\ncount=0\n[ ! -f "$file" ] || read -r count < "$file"\ncount=$((count + 1))\necho "$count" > "$file"\n[ "$count" -gt "$EXTENSION_FAILURES" ]\n`,{mode:0o700});
        writeFileSync(join(directory,'sleep'),'#!/bin/sh\nexit 0\n',{mode:0o700});
        const result=spawnSync('sh',[resolve('docker/install-pecl-extensions.sh'),'igbinary','redis'],{encoding:'utf8',env:{...process.env,PATH:directory+':'+process.env.PATH,EXTENSION_TEST_DIR:directory,EXTENSION_FAILURES:String(failures)}});
        const count=name=>{try{return Number(readFileSync(join(directory,name),'utf8'));}catch{return 0;}};
        return {status:result.status,error:result.stderr,igbinary:count('igbinary'),redis:count('redis')};
    } finally {rmSync(directory,{recursive:true,force:true});}
}
test('temporary extension download failures retry each package independently',()=>{
    const result=run(2);assert.equal(result.status,0);assert.equal(result.igbinary,3);assert.equal(result.redis,3);
});
test('persistent extension failure stops the build after three attempts',()=>{
    const result=run(3);assert.equal(result.status,1);assert.equal(result.igbinary,3);assert.equal(result.redis,0);assert.match(result.error,/after 3 attempts/);
});
