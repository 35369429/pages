"use strict"
import {color,tabBar, style as s, width } from '../App'
import {StyleSheet} from 'react-native';

let style = Object.assign(s, {
  wrapper: {
    padding:5,
    paddingTop:8,
    paddingBottom:8,
    borderBottomWidth:1,
    borderBottomColor:color.greyLight,
    flexDirection:'column',
    backgroundColor:color.white,
    minHeight:67.5,
    // alignItems:'center',
    // borderColor:"#FF0000",
    // borderWidth:1,
  },

  cover: {
    width:120,
    height:67.5,
    marginRight:5,
  },

  content: {
    width: width - 135,
    borderColor:'#FF0000',
    borderWidth:1,
  },

  sub:  {
    // borderColor:"#FF0000",
    // borderWidth:1,
    width: width - 135,
    display:'flex',
    flexDirection:'row',
    marginTop:5,
  },

  subItem: {
    // borderColor:"#FFF000",
    // borderWidth:1,
    paddingRight:5,
    color:color.textLight,
  },

  summary:{
    width: width - 135,
  },
  title: {
    fontSize:22,
    fontWeight:"400",
    color:color.text,
  },

  author:{
    marginTop:2,
    color:color.textLight,
  },
});

let articleStyle = {
  p:{
    padding:10,
    fontSize:16
  },
};

let articleOption= {
  image: {
    offset:30
  }
}

let articleCss = StyleSheet.create(articleStyle);
let css = StyleSheet.create(style);
export  { color, tabBar, style, css, articleCss,articleStyle, articleOption}
