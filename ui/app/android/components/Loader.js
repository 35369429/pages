import React, { Component } from 'react';
import Spinner from 'react-native-spinkit';
import { View, Text}  from 'react-native';
import { color, css, spinner } from 'skin/components/Loader';

export default class Loader extends React.Component {
  render() {
    console.log( 'Loader');
    return  (
      <View style={css.wrapper}>
        <Spinner style={css.spinner} isVisible={true} size={spinner.size} type={spinner.type} color={spinner.color}/>
      </View>
    )
  }
}
