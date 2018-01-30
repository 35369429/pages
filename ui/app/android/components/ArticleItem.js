import React, { Component } from 'react';
import { View, Text, Image, TouchableOpacity } from 'react-native';
import { color, css } from 'skin/components/ArticleItem';

export default class ArticleItem extends React.Component {


  render() {

    // console.log( this.props.navigate );
    // console.log( 'ArticleItem',  this.props.cover.url );

    // 根据不同场景显示不同结构
    // 没有封面的图片的
    if ( this.props.cover.url == null ) {
      return (
        <TouchableOpacity onPress={() => this.props.navigate('Detail', {article_id: this.props.article_id, title:this.props.title})}>
        <View style={[css.wrapper,css.inline]}>
            <View><Text style={[css.title]}>{this.props.title}</Text></View>
            <View style={css.sub}>
              <Text style={[css.subItem]}>{this.props.author}</Text>
              <Text style={[css.subItem]}>{this.props.publish_date}</Text>
            </View>
        </View>
        </TouchableOpacity>
      );
    } else {
      return  (
        <TouchableOpacity onPress={() => this.props.navigate('Detail', {article_id: this.props.article_id, title:this.props.title})}>
          <View style={css.wrapper}>
              <Image source={{uri:this.props.cover.url}}
                  style={css.cover} />
              <View style={css.content}>
                <Text style={[css.title]}>{this.props.title}</Text>
                <View style={css.sub}>
                  <Text style={[css.subItem]}>{this.props.author}</Text>
                  <Text style={[css.subItem]}>{this.props.publish_date}</Text>
                </View>
              </View>
          </View>
        </TouchableOpacity>
      )
    }
  }
}
