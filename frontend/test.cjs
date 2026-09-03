const faceapi = require('@vladmandic/face-api');
const canvas = require('canvas');

async function test() {
  const desc128 = new Float32Array(128);
  const desc512 = new Float32Array(512);

  try {
    const labeled = new faceapi.LabeledFaceDescriptors('test', [desc512]);
    const matcher = new faceapi.FaceMatcher([labeled]);
    const best = matcher.findBestMatch(desc128);
    console.log(best);
  } catch (e) {
    console.error('ERROR:', e.message);
  }
}

test();
