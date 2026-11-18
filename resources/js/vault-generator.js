const CHARACTER_SETS = Object.freeze({
    uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    lowercase: 'abcdefghijklmnopqrstuvwxyz',
    numbers: '0123456789',
    symbols: '!@#$%^&*()-_=+[]{}<>?',
});

export const PASSPHRASE_WORDS = Object.freeze([
    'amber', 'anchor', 'angle', 'apple', 'apron', 'arcade', 'arrow', 'artist', 'atlas', 'atom', 'audio', 'autumn', 'badge', 'bamboo', 'beacon', 'berry', 'beta', 'bicycle', 'birch', 'bird',
    'biscuit', 'blanket', 'blossom', 'blue', 'board', 'bolt', 'book', 'breeze', 'brick', 'bridge', 'bronze', 'brush', 'bubble', 'bucket', 'cabin', 'cable', 'cactus', 'camera', 'canyon', 'captain',
    'carbon', 'cargo', 'carpet', 'castle', 'cedar', 'center', 'chain', 'chalk', 'chance', 'channel', 'charm', 'chart', 'cherry', 'chess', 'chimney', 'cipher', 'circle', 'citrus', 'city', 'clarity',
    'clay', 'clock', 'cloud', 'clover', 'cobalt', 'coffee', 'comet', 'compass', 'copper', 'coral', 'cosmos', 'cotton', 'craft', 'crane', 'crater', 'creek', 'crest', 'cricket', 'crystal', 'current',
    'cyber', 'cycle', 'daisy', 'dancer', 'data', 'dawn', 'delta', 'denim', 'design', 'device', 'diamond', 'digital', 'diner', 'dolphin', 'domain', 'dragon', 'dream', 'drift', 'dune', 'eagle',
    'echo', 'eclipse', 'editor', 'ember', 'engine', 'evening', 'fabric', 'falcon', 'family', 'farmer', 'feather', 'field', 'fig', 'filter', 'finger', 'firefly', 'firmware', 'flame', 'flannel', 'flower',
    'focus', 'forest', 'format', 'fossil', 'frame', 'frost', 'galaxy', 'garden', 'garlic', 'gather', 'gecko', 'ginger', 'glacier', 'glimmer', 'globe', 'glory', 'golden', 'granite', 'grape', 'graph',
    'gravity', 'green', 'groove', 'guitar', 'harbor', 'harmony', 'harvest', 'hazel', 'helmet', 'heron', 'hickory', 'hill', 'honey', 'horizon', 'human', 'hybrid', 'icon', 'igloo', 'image', 'index',
    'indigo', 'inlet', 'insect', 'island', 'ivory', 'jacket', 'jaguar', 'jasmine', 'jazz', 'jelly', 'jigsaw', 'journey', 'joyful', 'jungle', 'juniper', 'kernel', 'kettle', 'key', 'kiwi', 'knight',
    'ladder', 'lagoon', 'lantern', 'laptop', 'laser', 'launch', 'leaf', 'legend', 'lemon', 'level', 'library', 'lilac', 'lizard', 'logic', 'lotus', 'lunar', 'magnet', 'maple', 'marble', 'market',
    'matrix', 'meadow', 'memory', 'mercury', 'mesa', 'meteor', 'method', 'midnight', 'minnow', 'mirror', 'mission', 'mobile', 'model', 'moment', 'monarch', 'monsoon', 'mosaic', 'mountain', 'mouse', 'music',
    'napkin', 'native', 'nebula', 'nectar', 'network', 'newton', 'nickel', 'night', 'nimble', 'noble', 'node', 'notebook', 'novel', 'number', 'nylon', 'oasis', 'ocean', 'octave', 'olive', 'onyx',
    'opal', 'orbit', 'orchid', 'origin', 'otter', 'outpost', 'oxygen', 'ozone', 'paddle', 'panda', 'paper', 'parade', 'parcel', 'parrot', 'pebble', 'pelican', 'pepper', 'phoenix', 'piano', 'pickle',
    'picture', 'pilot', 'pine', 'pixel', 'planet', 'plasma', 'plucky', 'pocket', 'poem', 'polar', 'pollen', 'poncho', 'prairie', 'prism', 'process', 'puppy', 'puzzle', 'quantum', 'quartz', 'quest',
    'quiet', 'quilt', 'rabbit', 'radar', 'radio', 'rainbow', 'raven', 'recipe', 'record', 'reef', 'remote', 'rescue', 'ribbon', 'river', 'robot', 'rocket', 'rose', 'ruby', 'saffron', 'sailor',
    'salmon', 'sample', 'sandal', 'saturn', 'scarlet', 'screen', 'script', 'season', 'secret', 'sensor', 'shadow', 'shamrock', 'shelter', 'silver', 'simple', 'signal', 'silk', 'sketch', 'solar', 'sonic',
    'spark', 'spectrum', 'spice', 'spider', 'spinach', 'splash', 'spring', 'spruce', 'stable', 'stadium', 'star', 'static', 'steam', 'stone', 'storm', 'strawberry', 'studio', 'sunbeam', 'sunrise', 'syntax',
    'tablet', 'tadpole', 'tangerine', 'target', 'teacup', 'temple', 'thunder', 'ticket', 'timber', 'tinsel', 'titan', 'tomato', 'topaz', 'tractor', 'traffic', 'travel', 'treaty', 'trident', 'tulip', 'tunnel',
    'turtle', 'tweezers', 'ultra', 'umpire', 'unicorn', 'unique', 'unison', 'update', 'uplink', 'useful', 'vacuum', 'valley', 'velvet', 'vendor', 'violet', 'vision', 'volcano', 'walnut', 'wander', 'water',
    'weasel', 'weather', 'whisper', 'widget', 'willow', 'window', 'winter', 'wizard', 'wonder', 'xenon', 'xylem', 'yacht', 'yellow', 'yonder', 'yummy', 'zephyr', 'zero', 'zinc', 'zipper', 'zodiac',
]);

function normalizedInteger(value, fallback, minimum, maximum) {
    const number = Number(value);
    const integer = Number.isFinite(number) ? Math.trunc(number) : fallback;

    return Math.min(maximum, Math.max(minimum, integer));
}

function secureRandomIndex(maximum) {
    if (!Number.isSafeInteger(maximum) || maximum < 1) {
        throw new RangeError('A positive random range is required.');
    }

    const range = 0x1_0000_0000;
    const limit = Math.floor(range / maximum) * maximum;
    const value = new Uint32Array(1);

    do {
        window.crypto.getRandomValues(value);
    } while (value[0] >= limit);

    return value[0] % maximum;
}

function shuffled(values) {
    const result = [...values];

    for (let index = result.length - 1; index > 0; index -= 1) {
        const swapIndex = secureRandomIndex(index + 1);

        [result[index], result[swapIndex]] = [result[swapIndex], result[index]];
    }

    return result;
}

function characterPools(options = {}) {
    const excludeAmbiguous = Boolean(options.excludeAmbiguous);
    const pools = Object.entries(CHARACTER_SETS)
        .filter(([name]) => options[name] !== false)
        .map(([name, characters]) => {
            if (!excludeAmbiguous) {
                return characters;
            }

            if (name === 'uppercase') {
                return characters.replace(/[IO]/g, '');
            }

            if (name === 'lowercase') {
                return characters.replace(/[lo]/g, '');
            }

            if (name === 'numbers') {
                return characters.replace(/[01]/g, '');
            }

            return characters;
        });

    if (pools.length === 0) {
        pools.push(CHARACTER_SETS.lowercase);
    }

    return pools;
}

export function generatePassword(options = {}) {
    const length = normalizedInteger(options.length, 24, 12, 64);
    const pools = characterPools(options);

    if (length < pools.length) {
        throw new RangeError('Password length must fit at least one character from each enabled set.');
    }

    const characters = pools.map((pool) => pool[secureRandomIndex(pool.length)]);
    const combinedPool = pools.join('');

    while (characters.length < length) {
        characters.push(combinedPool[secureRandomIndex(combinedPool.length)]);
    }

    return shuffled(characters).join('');
}

export function generatePassphrase(options = {}) {
    const wordCount = normalizedInteger(options.wordCount, 4, 3, 8);
    const separators = ['-', '_', '.', ' '];
    const separator = separators.includes(options.separator) ? options.separator : '-';
    const capitalize = options.capitalize !== false;
    const includeNumber = options.includeNumber !== false;
    const words = Array.from({ length: wordCount }, () => {
        const word = PASSPHRASE_WORDS[secureRandomIndex(PASSPHRASE_WORDS.length)];

        return capitalize ? `${word[0].toUpperCase()}${word.slice(1)}` : word;
    });

    if (includeNumber) {
        const wordIndex = secureRandomIndex(words.length);
        words[wordIndex] += String(10 + secureRandomIndex(90));
    }

    return words.join(separator);
}

export function calculateEntropy(secret, options = {}) {
    const mode = options.mode ?? 'password';
    const bits = mode === 'passphrase'
        ? Math.round(normalizedInteger(options.wordCount, 4, 3, 8) * Math.log2(PASSPHRASE_WORDS.length))
        : Math.round(String(secret ?? '').length * Math.log2(characterPools(options).join('').length));
    const label = bits >= 100 ? 'HIVE-GRADE' : (bits >= 60 ? 'STRONG' : 'FAIR');

    return { bits, label };
}
