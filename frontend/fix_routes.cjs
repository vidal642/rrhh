const fs = require('fs');
const path = require('path');

function replaceInFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let original = content;

    content = content.replace(/'\/employees/g, "'/empleados");
    content = content.replace(/`\/employees/g, "`/empleados");
    content = content.replace(/'\/departments/g, "'/departamentos");
    content = content.replace(/`\/departments/g, "`/departamentos");
    content = content.replace(/'\/positions/g, "'/cargos");
    content = content.replace(/`\/positions/g, "`/cargos");
    content = content.replace(/'\/panel/g, "'/dashboard");

    if (content !== original) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated: ${filePath}`);
    }
}

function walkDir(dir) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            walkDir(fullPath);
        } else if (fullPath.endsWith('.vue') || fullPath.endsWith('.js')) {
            replaceInFile(fullPath);
        }
    }
}

walkDir(path.join(__dirname, 'src'));
console.log('Done!');
