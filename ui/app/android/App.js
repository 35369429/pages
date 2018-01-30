/**
 * Sample React Native App
 * https://github.com/facebook/react-native
 * @flow
 */
import React, { Component } from 'react';
import {
  Dimensions, PixelRatio,StyleSheet,
  Text,
  Image,
  StatusBar,
  ToolbarAndroid,
  processColor,
  View,
  NativeModules,
} from 'react-native';

import { TabNavigator } from 'react-navigation';
import Home from './pages/Home';
import Reader from './pages/Reader';
import Inbox from './pages/Inbox';
import Member from './pages/Member';
import Create from './pages/Create';
import {tabBar, css, color} from 'skin/App';  // 载入皮肤


const AppTabs = TabNavigator({
  Home: {
    screen: Home,
    navigationOptions: {
      tabBarLabel: '首页',
      tabBarIcon: ({tintColor, focused})=>{
        return (
          <Image
            source={require('skin/res/icons/home.png')}
            style={[css.tintIcon, {tintColor: tintColor}]}
          />
        )
      }
    },
  },
  Reader: {
    screen: Reader,
    navigationOptions: {
      tabBarLabel: '悦读',
      tabBarIcon: ({tintColor, focused})=>{
        return (
          <Image
            source={require('skin/res/icons/reader.png')}
            style={[css.tintIcon, {tintColor: tintColor}]}
          />
        )
      }
    },
  },
  Create: {
    screen: Create,
    navigationOptions: {
      tabBarLabel: '发布',
      tabBarIcon: ({tintColor, focused})=>{
        return (
          <Image
            source={require('skin/res/icons/create.png')}
            style={[css.tintIcon, {tintColor: tintColor}]}
          />
        )
      }
    },
  },
  Inbox: {
    screen: Inbox,
    navigationOptions: {
      tabBarLabel: '消息',
      tabBarIcon: ({tintColor, focused})=>{
        return (
          <Image
            source={require('skin/res/icons/inbox.png')}
            style={[css.tintIcon, {tintColor: tintColor}]}
          />
        )
      }
    },
  },
  Member: {
    screen: Member,
    navigationOptions: {
      tabBarLabel: '我的',
      tabBarIcon: ({tintColor, focused})=>{
        return (
          <Image
            source={require('skin/res/icons/member.png')}
            style={[css.tintIcon, {tintColor: tintColor}]}
          />
        )
      }
    },
  },
}, tabBar);


export default class App extends React.Component {
  constructor(props) {
    super(props);
  }
  render() {
      // NativeModules.Window.setNavigationBarColor(processColor('#FFFFFF'));
      return (
        <View style={[css.bgWhite,{flex:1}]}  >
          <StatusBar backgroundColor={color.primary} />
          <Home />
          {/* <AppTabs ref={nav => { this.navigator = nav; }} /> */}
        </View>
      );
  }
}
