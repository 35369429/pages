"use strict"
import {color,tabBar, style as s, width } from '../App'
import {StyleSheet} from 'react-native';
let style = Object.assign(s, {
  wrapper: {
    width: width,
    height: width * 0.5625,
  },
});
let css = StyleSheet.create(style);
export  { color, tabBar, style, css }
