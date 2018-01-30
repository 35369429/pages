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
    flexDirection:'row',
    backgroundColor:color.white,
    minHeight:67.5,
    alignItems:'center',
    // borderColor:"#FF0000",
    // borderWidth:1,
  },

  inline:{
    flexDirection:"column",
    justifyContent:"flex-start",
    alignItems:'flex-start',
  },
  cover: {
    width:120,
    height:67.5,
    marginRight:5,
  },

  content: {
    width: width - 135,
  },

  sub:  {
    // borderColor:"#FF0000",
    // borderWidth:1,
    width: width - 135,
    display:'flex',
    flexDirection:'row'
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
    fontSize:16,
    fontWeight:'400',
    color:color.text,
  },

  author:{
    marginTop:2,
    color:color.textLight,
  },
});

let css = StyleSheet.create(style);
export  { color, tabBar, style, css }
