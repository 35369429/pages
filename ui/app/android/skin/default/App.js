/**
 * 全局样式
 */
"use strict"
import {StyleSheet,Dimensions,PixelRatio} from 'react-native';
export let {width,height} = Dimensions.get("window");
export let color = {
  primary:'#eb4e39',
  primaryLight:'#f0e6e5',
  text:'#333333',
  textLight:'#9f9e9d',
  grey:'#e6e6e6',
  greyLight:'#f5f5f5',
  greyDark:'#878787',
  light:'#FFFFFF',
  white:'#FFFFFF',
};

export let style = {
  bgWhite: {
    backgroundColor:color.white,
  },
  test: {
    borderColor:color.primary,
    borderWidth:1,
  },

  container: {
     flex: 1,
     justifyContent: 'center',
     alignItems: 'center',
     backgroundColor: color.light,
  },

  tintIcon:{
    width:24,
    height:24,
  },
};

export let tabBar = {
  tabBarPosition:'bottom',
  swipeEnabled:false,
  animationEnabled:false,
  tabBarOptions: {
    showIcon:true,
    activeTintColor:color.primary,
    inactiveTintColor:color.textLight,
    activeBackgroundColor:'transparent',
    pressColor:color.primaryLight,
    tabStyle: {
      borderWidth:0,
      borderColor:color.grey,
      borderTopWidth:1,
      paddingTop:2,
      paddingBottom:2,
    },
    labelStyle: {
      fontSize: 10,
      margin:0,
    },
    indicatorStyle: {
      backgroundColor:'transparent',
    },
    style:{
      backgroundColor:color.light,
      shadowOpacity:0,
      elevation:0,
    },
  },
};


export let css = StyleSheet.create(style);
