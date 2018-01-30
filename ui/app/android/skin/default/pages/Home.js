"use strict"
import {StyleSheet} from 'react-native';
import {color,tabBar,style as s, width} from '../App'

let style = Object.assign(s, {
  header: {
    backgroundColor:'#eb4e39',
    padding:5,
    flexDirection:'row',
    height:48,
  },
  title: {
    justifyContent:"space-between",
  },
  text: {
    marginTop:5,
    color:'#FFFFFF',
    fontSize:16,
    overflow:'hidden',
    maxWidth: width - 80,
    height:22,
  },

  btn: {
    marginTop:5,
    marginRight:10,
    width:24,
    height:24,
  },

  logo: {
    width:36,
    height:36
  },
  dark:{
    backgroundColor:'#000000',
    height:'100%',
  },
  light:{
    backgroundColor:'#FFFFFF',
    height:'100%',
  }
});

let tabNav = {
  swipeEnabled:true,
  animationEnabled:false,
  tabBarOptions: {
    showIcon:false,
    scrollEnabled:true,
    activeTintColor:color.primary,
    inactiveTintColor:color.textLight,
    activeBackgroundColor:color.primary,
    tabStyle: {
      padding:2,
      width:60,
    },
    indicatorStyle: {
      backgroundColor:color.light,
    },
    labelStyle: {
      fontSize: 16,
      fontWeight:'bold',
    },
    style:{
      borderColor:color.grey,
      borderWidth:0,
      borderBottomWidth:1,
      backgroundColor:color.light,
      shadowOpacity:0,
      elevation:0,
    }
  }
}

let css = StyleSheet.create(style);
export  { color, tabBar, style, css, width, tabNav }
