# Scene sources

`src/scene/*.js` are the sources of the 3D scenes: the front page house (`house.js`, `home.js`)
and the product page valve viewer (`viewer.js`, `product.js`). They are bundled into
`assets/js/scene/home.js` and `assets/js/scene/product.js`, which is what the theme loads.

To rebuild after editing:

    cd src && npm init -y && npm i three@0.170.0 esbuild
    sh build.sh

GSAP is not bundled: `src/scene/gsap.js` reads the copy the theme already loads (window.gsap).
