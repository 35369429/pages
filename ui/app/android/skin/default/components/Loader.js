"use strict"
import {color,tabBar, style as s, width } from '../App'
import {StyleSheet} from 'react-native';

let style = Object.assign(s, {
  spinner: {
    marginBottom: 50,
  },
  wrapper: {
    flex:1,
    justifyContent: 'center',
    alignItems:'center'
  }
});

let spinner = {
  size:50,
  color:color.primary,
  // https://github.com/maxs15/react-native-spinkit
  // available types
  // CircleFlip,Bounce,Wave,WanderingCubes,Pulse,ChasingDots,ThreeBounce,Circle,9CubeGrid,WordPress (IOS only),FadingCircle,FadingCircleAlt,Arc (IOS only),ArcAlt (IOS only)
  type:'Wave'
}
let css = StyleSheet.create(style);
export  { color, tabBar, style, css, spinner }
