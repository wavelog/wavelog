// ## License for the Code (MIT)

// Copyright (c) 2022 TalkJS

// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:

// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
// SOFTWARE.

// SOURCE: https://github.com/talkjs/country-flag-emoji-polyfill

const r = '"Twemoji Mozilla","Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji","EmojiOne Color","Android Emoji",sans-serif';
function a() {
  const o = document.createElement("canvas");
  o.width = o.height = 1;
  const t = o.getContext("2d", {willReadFrequently: true});
  return t.textBaseline = "top", t.font = `100px ${r}`, t.scale(0.01, 0.01), t;
}
function i(o, t, e) {
  return o.clearRect(0, 0, 100, 100), o.fillStyle = e, o.fillText(t, 0, 0), o.getImageData(0, 0, 1, 1).data.join(",");
}
function l(o) {
  const t = a(), e = i(t, o, "#fff"), n = i(t, o, "#000");
  return n === e && !n.startsWith("0,0,0,");
}
function f(o = "Twemoji Country Flags", t = base_url +'assets/fonts/TwemojiCountryFlags/TwemojiCountryFlags.woff2') {
  if (typeof window < "u" && l("\u{1F60A}") && !l("\u{1F1E8}\u{1F1ED}")) {
    const e = document.createElement("style");
    return e.textContent = `@font-face {
      font-family: "${o}";
      unicode-range: U+1F1E6-1F1FF, U+1F3F4, U+E0062-E0063, U+E0065, U+E0067,
        U+E006C, U+E006E, U+E0073-E0074, U+E0077, U+E007F;
      src: url('${t}') format('woff2');
      font-display: swap;
    }`, document.head.appendChild(e), true;
  }
  return false;
}
export {f as polyfillCountryFlagEmojis};
export default null;