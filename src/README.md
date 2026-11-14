# Scene sources

`src/scene/*.js` are the sources of the 3D front page (pipe manifold and exploded faucet).
They are bundled into `assets/js/scene/home.js`, which is what the theme loads.

To rebuild after editing:

    cd src && npm init -y && npm i three@0.170.0 esbuild
    sh build.sh

GSAP is not bundled: `src/scene/gsap.js` reads the copy the theme already loads (window.gsap).
