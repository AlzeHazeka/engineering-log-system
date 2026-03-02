import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "./resources/js/**/*.js",
    ],

    safelist: [
        // SYSTEM STATUS
        "bg-green-100",
        "text-green-700",
        "bg-yellow-100",
        "text-yellow-700",
        "bg-gray-100",
        "text-gray-700",
        "bg-red-100",
        "text-red-700",

        // LOG TYPE
        "bg-blue-100",
        "text-blue-700",
        "bg-purple-100",
        "text-purple-700",
        "bg-amber-100",
        "text-amber-700",
        "bg-orange-100",
        "text-orange-700",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
