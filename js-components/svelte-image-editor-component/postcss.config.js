import tailwindcss from '@tailwindcss/postcss';
import autoprefixer from 'autoprefixer';

const makeImportantPlugin = () => {
    return {
        postcssPlugin: 'make-important',
        Rule(rule) {
            const isUtility = rule.selectors.some(s => s.includes('.'));
            if (isUtility) {
                rule.walkDecls(decl => {
                    if (!decl.prop.startsWith('--')) {
                        decl.important = true;
                    }
                });
            }
        }
    };
};

export default {
    plugins: [
        tailwindcss(),
        autoprefixer(),
        makeImportantPlugin()
    ],
};
